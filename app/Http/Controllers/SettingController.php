<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingRequest;
use App\Models\Setting;
use App\Services\ActivityLogService;

class SettingController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService
    ) {}

    /**
     * Display the settings form.
     */
    public function index()
    {
        $setting = Setting::firstOrCreate([], [
            'nama_perusahaan' => 'Monitoring Fiber',
            'alamat' => '',
            'telepon' => '',
        ]);

        return view('settings.index', compact('setting'));
    }

    /**
     * Update the settings.
     */
    public function update(UpdateSettingRequest $request)
    {
        $setting = Setting::first();
        $setting->update($request->validated());

        $this->activityLogService->log(
            'Update Pengaturan',
            'Setting',
            'Mengubah pengaturan sistem'
        );

        return redirect()->route('settings.index')
            ->with('success', 'Pengaturan berhasil diperbarui.');
    }
}
