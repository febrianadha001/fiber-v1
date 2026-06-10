<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\Setting;
use App\Services\ActivityLogService;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly ActivityLogService $activityLogService
    ) {}

    /**
     * Daily report.
     */
    public function daily(Request $request)
    {
        $tanggalAwal = $request->get('tanggal_awal', now()->startOfMonth()->toDateString());
        $tanggalAkhir = $request->get('tanggal_akhir', now()->toDateString());

        $data = $this->reportService->getDailySummary(
            Carbon::parse($tanggalAwal),
            Carbon::parse($tanggalAkhir)
        );

        return view('reports.daily', compact('data', 'tanggalAwal', 'tanggalAkhir'));
    }

    /**
     * Monthly report.
     */
    public function monthly(Request $request)
    {
        $bulan = $request->get('bulan', now()->month);
        $tahun = $request->get('tahun', now()->year);

        $data = $this->reportService->getMonthlySummary((int) $bulan, (int) $tahun);

        return view('reports.monthly', compact('data', 'bulan', 'tahun'));
    }

    /**
     * Yearly report.
     */
    public function yearly(Request $request)
    {
        $tahun = $request->get('tahun', now()->year);

        $data = $this->reportService->getYearlySummary((int) $tahun);

        return view('reports.yearly', compact('data', 'tahun'));
    }

    /**
     * Export to Excel.
     */
    public function exportExcel(Request $request)
    {
        $data = $this->getFilteredData($request);
        $setting = Setting::first();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Trip');

        // Header perusahaan
        $sheet->setCellValue('A1', $setting->nama_perusahaan ?? 'Monitoring Fiber');
        $sheet->setCellValue('A2', 'Laporan Data Trip');
        $sheet->setCellValue('A3', $this->getFilterLabel($request));
        $sheet->mergeCells('A1:K1');
        $sheet->mergeCells('A2:K2');
        $sheet->mergeCells('A3:K3');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A1:A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Table headers
        $headers = ['No', 'Tanggal', 'Kendaraan', 'Kapasitas (Ton)', 'Harga Beli', 'Harga Jual', 'Modal', 'Hasil', 'Transport Amprah', 'Uang Jalan', 'Sisa Pembayaran', 'Profit'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '5', $header);
            $sheet->getStyle($col . '5')->getFont()->setBold(true);
            $sheet->getStyle($col . '5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4472C4');
            $sheet->getStyle($col . '5')->getFont()->getColor()->setRGB('FFFFFF');
            $sheet->getStyle($col . '5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $col++;
        }

        // Data rows
        $row = 6;
        $no = 1;
        foreach ($data['trips'] as $trip) {
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $trip->tanggal->format('d/m/Y'));
            $sheet->setCellValue('C' . $row, $trip->kendaraan);
            $sheet->setCellValue('D' . $row, $trip->kapasitas_tonase);
            $sheet->setCellValue('E' . $row, $trip->harga_beli);
            $sheet->setCellValue('F' . $row, $trip->harga_jual);
            $sheet->setCellValue('G' . $row, $trip->modal);
            $sheet->setCellValue('H' . $row, $trip->hasil);
            $sheet->setCellValue('I' . $row, $trip->transport_amprah);
            $sheet->setCellValue('J' . $row, $trip->uang_jalan);
            $sheet->setCellValue('K' . $row, $trip->sisa_pembayaran_uang_jalan);
            $sheet->setCellValue('L' . $row, $trip->profit);

            // Number format for currency
            foreach (['E', 'F', 'G', 'H', 'I', 'J', 'K', 'L'] as $c) {
                $sheet->getStyle($c . $row)->getNumberFormat()->setFormatCode('#,##0');
            }

            $row++;
            $no++;
        }

        // Summary row
        $sheet->setCellValue('A' . $row, '');
        $sheet->setCellValue('C' . $row, 'TOTAL');
        $sheet->getStyle('C' . $row)->getFont()->setBold(true);
        $sheet->setCellValue('D' . $row, $data['total_tonase']);
        $sheet->setCellValue('G' . $row, $data['total_modal']);
        $sheet->setCellValue('H' . $row, $data['total_hasil']);
        $sheet->setCellValue('I' . $row, $data['total_transport']);
        $sheet->setCellValue('J' . $row, $data['total_uang_jalan']);
        $sheet->setCellValue('L' . $row, $data['total_profit']);

        foreach (['D', 'G', 'H', 'I', 'J', 'L'] as $c) {
            $sheet->getStyle($c . $row)->getFont()->setBold(true);
            $sheet->getStyle($c . $row)->getNumberFormat()->setFormatCode('#,##0');
        }

        // Borders
        $sheet->getStyle('A5:L' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Auto width
        foreach (range('A', 'L') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $this->activityLogService->log('Export Excel', 'Laporan', 'Export laporan Excel');

        $filename = 'laporan_trip_' . now()->format('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Export to PDF.
     */
    public function exportPdf(Request $request)
    {
        $data = $this->getFilteredData($request);
        $setting = Setting::first();
        $filterLabel = $this->getFilterLabel($request);

        $pdf = Pdf::loadView('exports.report-pdf', compact('data', 'setting', 'filterLabel'))
            ->setPaper('a4', 'landscape');

        $this->activityLogService->log('Export PDF', 'Laporan', 'Export laporan PDF');

        return $pdf->download('laporan_trip_' . now()->format('Ymd_His') . '.pdf');
    }

    /**
     * Get filtered data based on request.
     */
    private function getFilteredData(Request $request): array
    {
        $type = $request->get('type', 'daily');

        return match ($type) {
            'monthly' => $this->reportService->getMonthlySummary(
                (int) $request->get('bulan', now()->month),
                (int) $request->get('tahun', now()->year)
            ),
            'yearly' => $this->reportService->getYearlySummary(
                (int) $request->get('tahun', now()->year)
            ),
            default => $this->reportService->getDailySummary(
                Carbon::parse($request->get('tanggal_awal', now()->startOfMonth())),
                Carbon::parse($request->get('tanggal_akhir', now()))
            ),
        };
    }

    /**
     * Get filter label for exports.
     */
    private function getFilterLabel(Request $request): string
    {
        $type = $request->get('type', 'daily');
        $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        return match ($type) {
            'monthly' => 'Periode: ' . $months[(int) $request->get('bulan', now()->month)] . ' ' . $request->get('tahun', now()->year),
            'yearly' => 'Periode: Tahun ' . $request->get('tahun', now()->year),
            default => 'Periode: ' . Carbon::parse($request->get('tanggal_awal', now()->startOfMonth()))->format('d/m/Y') . ' - ' . Carbon::parse($request->get('tanggal_akhir', now()))->format('d/m/Y'),
        };
    }
}
