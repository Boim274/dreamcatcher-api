<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AIQuotaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

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
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role') && in_array($request->role, ['admin', 'customer'])) {
            $query->where('role', $request->role);
        }

        // Stats counts
        $totalUsers = User::count();
        $totalAdmins = User::where('role', 'admin')->count();
        $totalCustomers = User::where('role', 'customer')->count();

        $perPage = $request->input('per_page', 15);
        $users = $query
            ->withCount('orders')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'phone' => $user->phone,
                'address' => $user->address,
                'avatar' => $user->avatar,
                'google_id' => $user->google_id ? true : false,
                'orders_count' => $user->orders_count ?? 0,
                'created_at' => $user->created_at,
                'quota' => $this->aiQuotaService->getQuota($user),
            ]);

        return response()->json([
            'users' => $users,
            'stats' => [
                'total' => $totalUsers,
                'admin_count' => $totalAdmins,
                'customer_count' => $totalCustomers,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => ['required', Rule::in(['admin', 'customer'])],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:1000',
            'generate_limit' => 'nullable|integer|min:0|max:999',
            'edit_limit' => 'nullable|integer|min:0|max:999',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'generate_limit' => $validated['generate_limit'] ?? 3,
            'edit_limit' => $validated['edit_limit'] ?? 3,
            'generate_used' => 0,
            'edit_used' => 0,
        ]);

        return response()->json([
            'message' => 'Pengguna baru berhasil ditambahkan.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'phone' => $user->phone,
                'address' => $user->address,
                'created_at' => $user->created_at,
                'quota' => $this->aiQuotaService->getQuota($user),
            ],
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(['admin', 'customer'])],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:1000',
            'password' => 'nullable|string|min:6',
        ]);

        // Prevent self-demotion if current user is logged-in admin
        if ($request->user() && $request->user()->id === $user->id && $validated['role'] !== 'admin') {
            return response()->json([
                'message' => 'Anda tidak dapat mengubah role akun admin Anda sendiri menjadi customer.',
            ], 422);
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];
        $user->phone = $validated['phone'] ?? null;
        $user->address = $validated['address'] ?? null;

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return response()->json([
            'message' => 'Data pengguna berhasil diperbarui.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'phone' => $user->phone,
                'address' => $user->address,
                'quota' => $this->aiQuotaService->getQuota($user),
            ],
        ]);
    }

    public function updateRole(Request $request, $id)
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(['admin', 'customer'])],
        ]);

        $user = User::findOrFail($id);

        // Prevent self-demotion
        if ($request->user() && $request->user()->id === $user->id && $validated['role'] !== 'admin') {
            return response()->json([
                'message' => 'Anda tidak dapat mengubah role akun Anda sendiri menjadi customer.',
            ], 422);
        }

        $user->role = $validated['role'];
        $user->save();

        return response()->json([
            'message' => "Role user {$user->name} berhasil diubah menjadi {$user->role}.",
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ],
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Prevent deleting own account
        if ($request->user() && $request->user()->id === $user->id) {
            return response()->json([
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri.',
            ], 422);
        }

        $userName = $user->name;
        $user->delete();

        return response()->json([
            'message' => "Pengguna {$userName} berhasil dihapus.",
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