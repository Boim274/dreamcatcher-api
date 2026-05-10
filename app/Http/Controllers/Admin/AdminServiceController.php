<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class AdminServiceController extends Controller
{
    public function index()
    {
        $services = Service::orderBy('created_at', 'desc')->get();

        return response()->json([
            'services' => $services,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'price_per_unit' => 'nullable|numeric|min:0',
            'minimum_order' => 'required|integer|min:1',
            'thumbnail' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $service = Service::create($validated);

        return response()->json([
            'message' => 'Service created successfully',
            'service' => $service,
        ], 201);
    }

    public function show($id)
    {
        $service = Service::with('portfolios')->findOrFail($id);

        return response()->json([
            'service' => $service,
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'base_price' => 'sometimes|numeric|min:0',
            'price_per_unit' => 'nullable|numeric|min:0',
            'minimum_order' => 'sometimes|integer|min:1',
            'thumbnail' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $service = Service::findOrFail($id);
        $service->update($validated);

        return response()->json([
            'message' => 'Service updated successfully',
            'service' => $service->fresh(),
        ]);
    }

    public function destroy($id)
    {
        $service = Service::findOrFail($id);

        if ($service->portfolios()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete service with portfolios. Please remove portfolios first.',
            ], 400);
        }

        $service->delete();

        return response()->json([
            'message' => 'Service deleted successfully',
        ]);
    }
}