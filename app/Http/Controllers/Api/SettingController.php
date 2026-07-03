<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::pluck('value', 'key')->toArray();

        return response()->json([
            'settings' => $settings,
        ]);
    }

    public function group($group)
    {
        $settings = Setting::where('group', $group)
            ->pluck('value', 'key')
            ->toArray();

        return response()->json([
            'settings' => $settings,
        ]);
    }
}
