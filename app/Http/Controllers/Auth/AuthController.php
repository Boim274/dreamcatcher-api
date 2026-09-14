<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        protected CloudinaryService $cloudinaryService
    ) {}

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => ['required', 'string', 'min:8', 'max:20', 'regex:/^\+?[0-9]{8,20}$/'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar. Silakan login atau gunakan email lain.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sesuai.',
            'phone.required' => 'Nomor HP wajib diisi.',
            'phone.min' => 'Nomor HP minimal 8 digit.',
            'phone.max' => 'Nomor HP maksimal 20 digit.',
            'phone.regex' => 'Format nomor HP tidak valid (contoh: +6281234567890).',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'role' => 'customer',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load('orders'),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => ['sometimes', 'nullable', 'string', 'min:8', 'max:20', 'regex:/^\+?[0-9]{8,20}$/'],
            'address' => 'sometimes|nullable|string',
            'avatar' => 'sometimes|nullable',
        ], [
            'phone.min' => 'Nomor HP minimal 8 digit.',
            'phone.max' => 'Nomor HP maksimal 20 digit.',
            'phone.regex' => 'Format nomor HP tidak valid (contoh: +6281234567890).',
        ]);

        $user = $request->user();

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $avatarUrl = null;

            try {
                $uploadResult = $this->cloudinaryService->upload($file);
                if ($uploadResult && !empty($uploadResult['url'])) {
                    $avatarUrl = $uploadResult['url'];
                }
            } catch (\Throwable $e) {
                Log::warning('Cloudinary avatar upload failed in updateProfile: ' . $e->getMessage());
            }

            if (!$avatarUrl) {
                $filename = 'avatar_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('avatars', $filename, 'public');
                $avatarUrl = asset('storage/' . $path);
            }
            $validated['avatar'] = $avatarUrl;
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Profil berhasil diperbarui!',
            'user' => $user->fresh(),
        ]);
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
        ], [
            'avatar.required' => 'File gambar foto profil wajib dipilih.',
            'avatar.image' => 'File harus berupa gambar.',
            'avatar.mimes' => 'Format gambar yang didukung: JPG, JPEG, PNG, WEBP, atau GIF.',
            'avatar.max' => 'Ukuran gambar maksimal 5 MB.',
        ]);

        $user = $request->user();
        $file = $request->file('avatar');
        $avatarUrl = null;

        try {
            $uploadResult = $this->cloudinaryService->upload($file);
            if ($uploadResult && !empty($uploadResult['url'])) {
                $avatarUrl = $uploadResult['url'];
            }
        } catch (\Throwable $e) {
            Log::warning('Cloudinary avatar upload failed, falling back to local storage: ' . $e->getMessage());
        }

        if (!$avatarUrl) {
            $filename = 'avatar_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('avatars', $filename, 'public');
            $avatarUrl = asset('storage/' . $path);
        }

        $user->update([
            'avatar' => $avatarUrl,
        ]);

        return response()->json([
            'message' => 'Foto profil berhasil diperbarui!',
            'avatar' => $avatarUrl,
            'user' => $user->fresh(),
        ]);
    }

    public function deleteAvatar(Request $request)
    {
        $user = $request->user();
        $user->update([
            'avatar' => null,
        ]);

        return response()->json([
            'message' => 'Foto profil berhasil dihapus.',
            'user' => $user->fresh(),
        ]);
    }

    public function googleAuth(Request $request)
    {
        $request->validate([
            'credential' => 'required_without:access_token|string|nullable',
            'access_token' => 'required_without:credential|string|nullable',
        ]);

        $googleData = null;

        if ($request->filled('credential')) {
            // Google ID Token (from Google Identity Services GSI)
            $response = Http::timeout(10)->get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $request->credential,
            ]);

            if ($response->successful()) {
                $googleData = $response->json();
            }
        } elseif ($request->filled('access_token')) {
            // OAuth2 Access Token
            $response = Http::timeout(10)->withToken($request->access_token)->get('https://www.googleapis.com/oauth2/v3/userinfo');

            if ($response->successful()) {
                $googleData = $response->json();
            }
        }

        if (!$googleData || empty($googleData['email'])) {
            throw ValidationException::withMessages([
                'google' => ['Gagal memverifikasi akun Google. Token tidak valid atau kedaluwarsa.'],
            ]);
        }

        $email = strtolower(trim($googleData['email']));
        $googleId = $googleData['sub'] ?? ($googleData['id'] ?? null);
        $name = $googleData['name'] ?? explode('@', $email)[0];
        $avatar = $googleData['picture'] ?? null;

        // Cari user berdasarkan google_id atau email
        $user = User::where('google_id', $googleId)
            ->orWhere('email', $email)
            ->first();

        $isNewUser = false;

        if ($user) {
            $updates = [];
            if (empty($user->google_id) && $googleId) {
                $updates['google_id'] = $googleId;
            }
            if (empty($user->avatar) && $avatar) {
                $updates['avatar'] = $avatar;
            }
            if (empty($user->email_verified_at)) {
                $updates['email_verified_at'] = now();
            }
            if (!empty($updates)) {
                $user->update($updates);
            }
        } else {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'google_id' => $googleId,
                'avatar' => $avatar,
                'password' => Hash::make(Str::random(32)),
                'role' => 'customer',
                'email_verified_at' => now(),
            ]);
            $isNewUser = true;
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => $isNewUser ? 'Pendaftaran dengan akun Google berhasil!' : 'Login dengan Google berhasil!',
            'user' => $user,
            'token' => $token,
            'is_new_user' => $isNewUser,
            'needs_phone' => empty($user->phone),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();
        $isGoogleUser = !empty($user->google_id);

        $rules = [
            'password' => 'required|string|min:8|confirmed',
        ];

        if (!$isGoogleUser) {
            $rules['current_password'] = 'required|string';
        } else {
            $rules['current_password'] = 'nullable|string';
        }

        $validated = $request->validate($rules, [
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'current_password.required' => 'Password saat ini wajib diisi.',
        ]);

        if (!$isGoogleUser || !empty($validated['current_password'])) {
            if (!Hash::check($validated['current_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['Password saat ini tidak sesuai.'],
                ]);
            }
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => $isGoogleUser ? 'Password berhasil dibuat! Anda kini dapat login dengan Email & Password maupun Google.' : 'Password berhasil diubah!',
        ]);
    }
}