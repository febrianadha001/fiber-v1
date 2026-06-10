<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->index();
            $table->string('kendaraan', 20)->index();
            $table->decimal('kapasitas_tonase', 10, 2);
            $table->decimal('harga_beli', 15, 2);
            $table->decimal('harga_jual', 15, 2);
            $table->decimal('modal', 15, 2)->default(0);
            $table->decimal('hasil', 15, 2)->default(0);
            $table->decimal('transport_amprah', 15, 2)->default(0);
            $table->decimal('uang_jalan', 15, 2)->default(0);
            $table->decimal('sisa_pembayaran_uang_jalan', 15, 2)->default(0);
            $table->decimal('profit', 15, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
