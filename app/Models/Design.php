<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Design extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_session',
        'prompt',
        'image_url',
        'color_count',
        'has_gradient',
        'has_shadow',
        'contrast_score',
        'is_valid',
        'design_type',
        'validation_message',
    ];

    protected $casts = [
        'has_gradient' => 'boolean',
        'has_shadow' => 'boolean',
        'is_valid' => 'boolean',
        'contrast_score' => 'decimal:2',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}