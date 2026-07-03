<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class AdminServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Service::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('pricing_type')) {
            $query->where('pricing_type', $request->pricing_type);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $services = $query->orderBy('created_at', 'desc')->paginate(15);

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
            'pricing_type' => 'required|in:flat,tiered',
            'pricing_config' => 'nullable|array',
            'options_config' => 'nullable|array',
        ]);

        if (isset($validated['pricing_config']) && is_array($validated['pricing_config'])) {
            $validated['pricing_config'] = json_encode($validated['pricing_config']);
        }
        if (isset($validated['options_config']) && is_array($validated['options_config'])) {
            $validated['options_config'] = json_encode($validated['options_config']);
        }

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
            'pricing_type' => 'sometimes|in:flat,tiered',
            'pricing_config' => 'nullable|array',
            'options_config' => 'nullable|array',
        ]);

        if (isset($validated['pricing_config']) && is_array($validated['pricing_config'])) {
            $validated['pricing_config'] = json_encode($validated['pricing_config']);
        }
        if (isset($validated['options_config']) && is_array($validated['options_config'])) {
            $validated['options_config'] = json_encode($validated['options_config']);
        }

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
