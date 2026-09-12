<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class AIQuotaService
{
    /**
     * Admins are treated as an unlimited "Pro" account (no token limit).
     */
    private function isUnlimited(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Get the generate/edit quota summary for a user.
     */
    public function getQuota(User $user): array
    {
        $summary = static fn (string $used, string $limit, bool $unlimited): array => [
            'used' => $unlimited ? null : (int) $user->{$used},
            'limit' => $unlimited ? null : (int) $user->{$limit},
            'remaining' => $unlimited ? null : max(0, (int) $user->{$limit} - (int) $user->{$used}),
            'unlimited' => $unlimited,
        ];

        $unlimited = $this->isUnlimited($user);

        return [
            'generate' => $summary('generate_used', 'generate_limit', $unlimited),
            'edit' => $summary('edit_used', 'edit_limit', $unlimited),
            'last_generate_at' => $user->last_generate_at?->toISOString(),
            'last_edit_at' => $user->last_edit_at?->toISOString(),
        ];
    }

    /**
     * Whether the user still has generate quota (cheap pre-check before AI).
     */
    public function hasGenerateRemaining(User $user): bool
    {
        if ($this->isUnlimited($user)) {
            return true;
        }
        return (int) $user->generate_used < (int) $user->generate_limit;
    }

    /**
     * Whether the user still has edit quota.
     */
    public function hasEditRemaining(User $user): bool
    {
        if ($this->isUnlimited($user)) {
            return true;
        }
        return (int) $user->edit_used < (int) $user->edit_limit;
    }

    /**
     * Atomically check and consume one generate quota. Returns false when the
     * user has no remaining quota (no AI request should be sent in that case).
     * Unlimiteed (admin) accounts always consume without incrementing.
     */
    public function consumeGenerate(User $user): bool
    {
        if ($this->isUnlimited($user)) {
            return true;
        }
        return DB::transaction(function () use ($user) {
            $locked = User::whereKey($user->id)->lockForUpdate()->first();
            if (!$locked || (int) $locked->generate_used >= (int) $locked->generate_limit) {
                return false;
            }
            $locked->increment('generate_used');
            $locked->update(['last_generate_at' => now()]);
            return true;
        });
    }

    /**
     * Atomically check and consume one edit quota.
     * Unlimited (admin) accounts always consume without incrementing.
     */
    public function consumeEdit(User $user): bool
    {
        if ($this->isUnlimited($user)) {
            return true;
        }
        return DB::transaction(function () use ($user) {
            $locked = User::whereKey($user->id)->lockForUpdate()->first();
            if (!$locked || (int) $locked->edit_used >= (int) $locked->edit_limit) {
                return false;
            }
            $locked->increment('edit_used');
            $locked->update(['last_edit_at' => now()]);
            return true;
        });
    }

    /**
     * Reset consumed quota and/or change the limits (used by admin).
     */
    public function resetQuota(User $user, ?int $generateLimit = null, ?int $editLimit = null): void
    {
        $data = [
            'generate_used' => 0,
            'edit_used' => 0,
            'last_generate_at' => null,
            'last_edit_at' => null,
        ];

        if ($generateLimit !== null) {
            $data['generate_limit'] = max(0, $generateLimit);
        }
        if ($editLimit !== null) {
            $data['edit_limit'] = max(0, $editLimit);
        }

        $user->update($data);
    }
}