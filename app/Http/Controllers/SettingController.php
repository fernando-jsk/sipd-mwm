<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->keyBy('key');
        
        return Inertia::render('Settings/Index', [
            'settings' => $settings
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable|string'
        ]);

        foreach ($validated['settings'] as $settingData) {
            Setting::where('key', $settingData['key'])->update(['value' => $settingData['value']]);
        }

        activity('setting')
            ->log('Memperbarui pengaturan sistem');

        return redirect()->back()->with('message', 'Pengaturan berhasil disimpan');
    }
}
