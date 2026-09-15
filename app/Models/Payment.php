<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'payment_method',
        'payment_proof',
        'cloudinary_public_id',
        'amount',
        'payment_type',
        'payment_status',
        'notes',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'verified_at' => 'datetime',
    ];

    protected $appends = ['thumbnail_url'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopeVerified($query)
    {
        return $query->where('payment_status', 'verified');
    }

    public function getThumbnailUrlAttribute(): string
    {
        $url = $this->payment_proof;
        if (empty($url)) return '';

        if (str_contains($url, 'res.cloudinary.com')) {
            if (str_contains($url, '/image/upload/')) {
                return str_replace('/image/upload/', '/image/upload/c_fill,w_200,h_200,g_auto,q_auto,f_auto/', $url);
            }
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
}