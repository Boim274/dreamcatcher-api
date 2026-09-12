<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AIQuotaService;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function __construct(private AIQuotaService $aiQuotaService) {}

    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'phone' => $user->phone,
                'created_at' => $user->created_at,
                'quota' => $this->aiQuotaService->getQuota($user),
            ]);

        return response()->json([
            'users' => $users,
        ]);
    }

    public function updateQuota(Request $request, $id)
    {
        $validated = $request->validate([
            'generate_limit' => 'nullable|integer|min:0|max:999',
            'edit_limit' => 'nullable|integer|min:0|max:999',
        ]);

        $user = User::findOrFail($id);

        $this->aiQuotaService->resetQuota(
            $user,
            array_key_exists('generate_limit', $validated) ? (int) $validated['generate_limit'] : null,
            array_key_exists('edit_limit', $validated) ? (int) $validated['edit_limit'] : null
        );

        return response()->json([
            'message' => 'Kuota AI user diperbarui.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'quota' => $this->aiQuotaService->getQuota($user->fresh()),
            ],
        ]);
    }
}