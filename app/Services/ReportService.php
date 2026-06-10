<?php

namespace App\Services;

use App\Models\Trip;
use Illuminate\Support\Carbon;

class ReportService
{
    /**
     * Get summary data for a date range.
     */
    public function getSummary($query): array
    {
        return [
            'total_trip' => (clone $query)->count(),
            'total_tonase' => (clone $query)->sum('kapasitas_tonase'),
            'total_modal' => (clone $query)->sum('modal'),
            'total_hasil' => (clone $query)->sum('hasil'),
            'total_transport' => (clone $query)->sum('transport_amprah'),
            'total_uang_jalan' => (clone $query)->sum('uang_jalan'),
            'total_profit' => (clone $query)->sum('profit'),
        ];
    }

    /**
     * Get daily report summary.
     */
    public function getDailySummary(Carbon $start, Carbon $end): array
    {
        $query = Trip::whereBetween('tanggal', [$start->toDateString(), $end->toDateString()]);
        $summary = $this->getSummary($query);
        $summary['trips'] = Trip::with('creator')
            ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
            ->orderBy('tanggal', 'desc')
            ->get();

        return $summary;
    }

    /**
     * Get monthly report summary.
     */
    public function getMonthlySummary(int $month, int $year): array
    {
        $query = Trip::whereMonth('tanggal', $month)->whereYear('tanggal', $year);
        $summary = $this->getSummary($query);
        $summary['trips'] = Trip::with('creator')
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->orderBy('tanggal', 'desc')
            ->get();

        return $summary;
    }

    /**
     * Get yearly report summary.
     */
    public function getYearlySummary(int $year): array
    {
        $query = Trip::whereYear('tanggal', $year);
        $summary = $this->getSummary($query);
        $summary['trips'] = Trip::with('creator')
            ->whereYear('tanggal', $year)
            ->orderBy('tanggal', 'desc')
            ->get();

        return $summary;
    }

    /**
     * Get dashboard data with monthly chart data.
     */
    public function getDashboardData(?int $year = null): array
    {
        $year = $year ?? now()->year;

        $query = Trip::query();
        $summary = $this->getSummary($query);

        // Monthly chart data
        $monthlyData = Trip::selectRaw("
                MONTH(tanggal) as bulan,
                COUNT(*) as total_trip,
                SUM(kapasitas_tonase) as total_tonase,
                SUM(modal) as total_modal,
                SUM(transport_amprah) as total_transport,
                SUM(profit) as total_profit
            ")
            ->whereYear('tanggal', $year)
            ->groupByRaw('MONTH(tanggal)')
            ->orderByRaw('MONTH(tanggal)')
            ->get()
            ->keyBy('bulan');

        $months = [];
        $profitData = [];
        $tonaseData = [];
        $pengeluaranData = [];
        $tripData = [];
        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        for ($i = 1; $i <= 12; $i++) {
            $months[] = $monthNames[$i - 1];
            $data = $monthlyData->get($i);
            $profitData[] = $data ? (float) $data->total_profit : 0;
            $tonaseData[] = $data ? (float) $data->total_tonase : 0;
            $pengeluaranData[] = $data ? ((float) $data->total_modal + (float) $data->total_transport) : 0;
            $tripData[] = $data ? (int) $data->total_trip : 0;
        }

        $summary['chart'] = [
            'labels' => $months,
            'profit' => $profitData,
            'tonase' => $tonaseData,
            'pengeluaran' => $pengeluaranData,
            'trip' => $tripData,
        ];
        $summary['year'] = $year;

        return $summary;
    }
}
