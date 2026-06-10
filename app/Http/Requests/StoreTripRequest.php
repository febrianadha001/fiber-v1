<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date'],
            'kendaraan' => ['required', 'string', 'max:20'],
            'kapasitas_tonase' => ['required', 'numeric', 'min:0'],
            'harga_beli' => ['required', 'numeric', 'min:0'],
            'harga_jual' => ['required', 'numeric', 'min:0'],
            'transport_amprah' => ['required', 'numeric', 'min:0'],
            'uang_jalan' => ['required', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'tanggal.required' => 'Tanggal wajib diisi.',
            'kendaraan.required' => 'Nomor kendaraan wajib diisi.',
            'kendaraan.max' => 'Nomor kendaraan maksimal 20 karakter.',
            'kapasitas_tonase.required' => 'Kapasitas tonase wajib diisi.',
            'kapasitas_tonase.numeric' => 'Kapasitas tonase harus berupa angka.',
            'harga_beli.required' => 'Harga beli wajib diisi.',
            'harga_jual.required' => 'Harga jual wajib diisi.',
            'transport_amprah.required' => 'Transport amprah wajib diisi.',
            'uang_jalan.required' => 'Uang jalan wajib diisi.',
        ];
    }
}
