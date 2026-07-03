<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class AdminSettingController extends Controller
{
    public function index()
    {
        $settings = Setting::orderBy('group')->orderBy('key')->get();

        return response()->json([
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable|string',
            'settings.*.group' => 'nullable|string',
        ]);

        foreach ($validated['settings'] as $item) {
            Setting::updateOrCreate(
                ['key' => $item['key']],
                [
                    'value' => $item['value'],
                    'group' => $item['group'] ?? 'general',
                ]
            );
        }

        return response()->json([
            'message' => 'Pengaturan berhasil disimpan',
        ]);
    }

    public function destroy($id)
    {
        $setting = Setting::findOrFail($id);
        $setting->delete();

        return response()->json([
            'message' => 'Pengaturan berhasil dihapus',
        ]);
    }
}
