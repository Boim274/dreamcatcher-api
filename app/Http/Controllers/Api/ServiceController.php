<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::active()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'services' => $services,
        ]);
    }

    public function show($id)
    {
        $service = Service::findOrFail($id);

        return response()->json([
            'service' => $service,
        ]);
    }
}