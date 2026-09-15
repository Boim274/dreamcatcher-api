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

    protected $appends = ['thumbnail_url', 'preview_url'];

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

    public function getThumbnailUrlAttribute(): string
    {
        $url = $this->image_url;
        if (empty($url)) return '';

        if (str_contains($url, 'res.cloudinary.com')) {
            if (str_contains($url, '/image/upload/')) {
                return str_replace('/image/upload/', '/image/upload/c_fill,w_250,h_250,g_auto,q_auto,f_auto/', $url);
            }
        }

        if (str_contains($url, 'images.unsplash.com')) {
            $base = preg_replace('/[?&]w=\d+/', '', $url);
            $sep = str_contains($base, '?') ? '&' : '?';
            return "{$base}{$sep}w=250&h=250&fit=crop&auto=format&q=75";
        }

        // Local thumb check
        $thumbPath = preg_replace('/\.(png|jpg|jpeg|webp)$/i', '-thumb.webp', $url);
        $fullPath = public_path(ltrim(str_replace('storage/', 'storage/', $thumbPath), '/'));
        if (file_exists($fullPath)) {
            return $thumbPath;
        }

        return $url;
    }

    public function getPreviewUrlAttribute(): string
    {
        $url = $this->image_url;
        if (empty($url)) return '';

        if (str_contains($url, 'res.cloudinary.com')) {
            if (str_contains($url, '/image/upload/')) {
                return str_replace('/image/upload/', '/image/upload/c_limit,w_800,q_auto,f_auto/', $url);
            }
        }

        if (str_contains($url, 'images.unsplash.com')) {
            $base = preg_replace('/[?&]w=\d+/', '', $url);
            $sep = str_contains($base, '?') ? '&' : '?';
            return "{$base}{$sep}w=800&auto=format&q=85";
        }

        // Local preview check
        $prevPath = preg_replace('/\.(png|jpg|jpeg|webp)$/i', '-preview.webp', $url);
        $fullPath = public_path(ltrim(str_replace('storage/', 'storage/', $prevPath), '/'));
        if (file_exists($fullPath)) {
            return $prevPath;
        }

        return $url;
    }
}