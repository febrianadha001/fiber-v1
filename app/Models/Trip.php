<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trip extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tanggal',
        'kendaraan',
        'kapasitas_tonase',
        'harga_beli',
        'harga_jual',
        'modal',
        'hasil',
        'transport_amprah',
        'uang_jalan',
        'sisa_pembayaran_uang_jalan',
        'profit',
        'keterangan',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'kapasitas_tonase' => 'decimal:2',
            'harga_beli' => 'decimal:2',
            'harga_jual' => 'decimal:2',
            'modal' => 'decimal:2',
            'hasil' => 'decimal:2',
            'transport_amprah' => 'decimal:2',
            'uang_jalan' => 'decimal:2',
            'sisa_pembayaran_uang_jalan' => 'decimal:2',
            'profit' => 'decimal:2',
        ];
    }

    /**
     * Get the user who created this trip.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
