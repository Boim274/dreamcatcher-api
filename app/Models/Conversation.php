<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_session',
        'title',
        'emoji',
        'pinned',
        'status',
        'trashed_at',
    ];

    protected $casts = [
        'pinned' => 'boolean',
        'trashed_at' => 'datetime',
    ];

    public function messages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function designs()
    {
        return $this->hasMany(Design::class);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where('title', 'like', '%' . $term . '%');
    }

    public function scopeOrdered(Builder $query, string $sort = 'newest'): Builder
    {
        $query->orderBy('pinned', 'desc');
        $query->orderBy('updated_at', $sort === 'oldest' ? 'asc' : 'desc');

        return $query;
    }
}
