<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Portfolio extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'image_url',
        'cloudinary_public_id',
        'service_id',
        'description',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = ['thumbnail_url', 'medium_url'];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderByDesc('created_at');
    }

    public function getThumbnailUrlAttribute(): string
    {
        $url = $this->image_url;
        if (empty($url)) return '';

        if (str_contains($url, 'res.cloudinary.com')) {
            if (str_contains($url, '/image/upload/')) {
                return str_replace('/image/upload/', '/image/upload/c_fill,w_350,h_350,g_auto,q_auto,f_auto/', $url);
            }
        }

        if (str_contains($url, 'images.unsplash.com')) {
            $base = preg_replace('/[?&]w=\d+/', '', $url);
            $sep = str_contains($base, '?') ? '&' : '?';
            return "{$base}{$sep}w=350&h=350&fit=crop&auto=format&q=75";
        }

        if (str_contains($url, '.webp') && !str_contains($url, '-thumb.webp')) {
            $thumbUrl = str_replace('.webp', '-thumb.webp', $url);
            $fullThumbPath = public_path(ltrim(str_replace('storage/', 'storage/', $thumbUrl), '/'));
            if (file_exists($fullThumbPath)) {
                return $thumbUrl;
            }
        }

        return $url;
    }

    public function getMediumUrlAttribute(): string
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
            return "{$base}{$sep}w=800&auto=format&q=80";
        }

        if (str_contains($url, '.webp') && !str_contains($url, '-medium.webp')) {
            $medUrl = str_replace('.webp', '-medium.webp', $url);
            $fullMedPath = public_path(ltrim(str_replace('storage/', 'storage/', $medUrl), '/'));
            if (file_exists($fullMedPath)) {
                return $medUrl;
            }
        }

        return $url;
    }
}