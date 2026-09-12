<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Design extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_session',
        'conversation_id',
        'parent_id',
        'prompt',
        'image_url',
        'cloudinary_public_id',
        'color_count',
        'has_gradient',
        'has_shadow',
        'contrast_score',
        'is_valid',
        'design_type',
        'kind',
        'version',
        'setting',
        'validation_message',
    ];

    protected $casts = [
        'has_gradient' => 'boolean',
        'has_shadow' => 'boolean',
        'is_valid' => 'boolean',
        'contrast_score' => 'decimal:2',
        'setting' => 'array',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}