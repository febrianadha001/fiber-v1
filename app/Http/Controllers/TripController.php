<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTripRequest;
use App\Http\Requests\UpdateTripRequest;
use App\Models\Trip;
use App\Services\ActivityLogService;
use App\Services\TripService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class TripController extends Controller
{
    public function __construct(
        private readonly TripService $tripService,
        private readonly ActivityLogService $activityLogService
    ) {}

    /**
     * Display a listing of trips.
     */
    public function index()
    {
        return view('trips.index');
    }

    /**
     * DataTables server-side data.
     */
    public function data(Request $request): JsonResponse
    {
        $query = Trip::with('creator')->select('trips.*');

        // Date filters
        if ($request->filled('tanggal_awal') && $request->filled('tanggal_akhir')) {
            $query->whereBetween('tanggal', [$request->tanggal_awal, $request->tanggal_akhir]);
        }

        if ($request->filled('bulan')) {
            $query->whereMonth('tanggal', $request->bulan);
        }

        if ($request->filled('tahun')) {
            $query->whereYear('tanggal', $request->tahun);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('tanggal', function ($trip) {
                return $trip->tanggal->format('d/m/Y');
            })
            ->editColumn('kapasitas_tonase', function ($trip) {
                return number_format($trip->kapasitas_tonase, 2, ',', '.');
            })
            ->editColumn('harga_beli', function ($trip) {
                return 'Rp ' . number_format($trip->harga_beli, 0, ',', '.');
            })
            ->editColumn('harga_jual', function ($trip) {
                return 'Rp ' . number_format($trip->harga_jual, 0, ',', '.');
            })
            ->editColumn('modal', function ($trip) {
                return 'Rp ' . number_format($trip->modal, 0, ',', '.');
            })
            ->editColumn('hasil', function ($trip) {
                return 'Rp ' . number_format($trip->hasil, 0, ',', '.');
            })
            ->editColumn('transport_amprah', function ($trip) {
                return 'Rp ' . number_format($trip->transport_amprah, 0, ',', '.');
            })
            ->editColumn('uang_jalan', function ($trip) {
                return 'Rp ' . number_format($trip->uang_jalan, 0, ',', '.');
            })
            ->editColumn('sisa_pembayaran_uang_jalan', function ($trip) {
                return 'Rp ' . number_format($trip->sisa_pembayaran_uang_jalan, 0, ',', '.');
            })
            ->editColumn('profit', function ($trip) {
                $class = $trip->profit >= 0 ? 'text-success' : 'text-danger';
                return '<span class="' . $class . ' fw-bold">Rp ' . number_format($trip->profit, 0, ',', '.') . '</span>';
            })
            ->addColumn('action', function ($trip) {
                $editBtn = '<a href="' . route('trips.edit', $trip) . '" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></a>';
                $deleteBtn = '';
                if (auth()->user()->isAdmin()) {
                    $deleteBtn = ' <button class="btn btn-sm btn-danger btn-delete" data-id="' . $trip->id . '" title="Hapus"><i class="fas fa-trash"></i></button>';
                }
                return $editBtn . $deleteBtn;
            })
            ->rawColumns(['profit', 'action'])
            ->make(true);
    }

    /**
     * Show the form for creating a new trip.
     */
    public function create()
    {
        return view('trips.create');
    }

    /**
     * Store a newly created trip.
     */
    public function store(StoreTripRequest $request)
    {
        $trip = $this->tripService->store($request->validated());

        $this->activityLogService->log(
            'Tambah Data',
            'Trip',
            "Menambahkan data trip kendaraan {$trip->kendaraan} tanggal {$trip->tanggal->format('d/m/Y')}"
        );

        return redirect()->route('trips.index')
            ->with('success', 'Data trip berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified trip.
     */
    public function edit(Trip $trip)
    {
        return view('trips.edit', compact('trip'));
    }

    /**
     * Update the specified trip.
     */
    public function update(UpdateTripRequest $request, Trip $trip)
    {
        $this->tripService->update($trip, $request->validated());

        $this->activityLogService->log(
            'Edit Data',
            'Trip',
            "Mengubah data trip kendaraan {$trip->kendaraan} tanggal {$trip->tanggal->format('d/m/Y')}"
        );

        return redirect()->route('trips.index')
            ->with('success', 'Data trip berhasil diperbarui.');
    }

    /**
     * Remove the specified trip (soft delete).
     */
    public function destroy(Trip $trip)
    {
        $kendaraan = $trip->kendaraan;
        $tanggal = $trip->tanggal->format('d/m/Y');

        $this->tripService->delete($trip);

        $this->activityLogService->log(
            'Hapus Data',
            'Trip',
            "Menghapus data trip kendaraan {$kendaraan} tanggal {$tanggal}"
        );

        return response()->json(['success' => true, 'message' => 'Data trip berhasil dihapus.']);
    }

    /**
     * AJAX endpoint for realtime calculation.
     */
    public function calculate(Request $request): JsonResponse
    {
        $calculated = $this->tripService->calculateFields($request->all());

        return response()->json($calculated);
    }
}
