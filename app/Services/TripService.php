<?php

namespace App\Services;

use App\Models\Trip;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TripService
{
    /**
     * Calculate computed fields from input data.
     *
     * Modal = Kapasitas Tonase × Harga Beli
     * Hasil = Kapasitas Tonase × Harga Jual
     * Sisa Pembayaran Uang Jalan = Transport Amprah − Uang Jalan
     * Profit = Hasil − Modal − Transport Amprah
     */
    public function calculateFields(array $data): array
    {
        $kapasitas = (float) ($data['kapasitas_tonase'] ?? 0);
        $hargaBeli = (float) ($data['harga_beli'] ?? 0);
        $hargaJual = (float) ($data['harga_jual'] ?? 0);
        $transportAmprah = (float) ($data['transport_amprah'] ?? 0);
        $uangJalan = (float) ($data['uang_jalan'] ?? 0);

        $modal = $kapasitas * $hargaBeli;
        $hasil = $kapasitas * $hargaJual;
        $sisaPembayaran = $transportAmprah - $uangJalan;
        $profit = $hasil - $modal - $transportAmprah;

        return [
            'modal' => round($modal, 2),
            'hasil' => round($hasil, 2),
            'sisa_pembayaran_uang_jalan' => round($sisaPembayaran, 2),
            'profit' => round($profit, 2),
        ];
    }

    /**
     * Store a new trip with calculated fields.
     */
    public function store(array $data): Trip
    {
        return DB::transaction(function () use ($data) {
            $calculated = $this->calculateFields($data);
            $data = array_merge($data, $calculated);
            $data['created_by'] = Auth::id();

            return Trip::create($data);
        });
    }

    /**
     * Update an existing trip with recalculated fields.
     */
    public function update(Trip $trip, array $data): Trip
    {
        return DB::transaction(function () use ($trip, $data) {
            $calculated = $this->calculateFields($data);
            $data = array_merge($data, $calculated);

            $trip->update($data);

            return $trip->fresh();
        });
    }

    /**
     * Soft delete a trip.
     */
    public function delete(Trip $trip): bool
    {
        return DB::transaction(function () use ($trip) {
            return $trip->delete();
        });
    }
}
