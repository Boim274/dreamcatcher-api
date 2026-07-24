<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_code',
        'customer_name',
        'customer_email',
        'phone',
        'address',
        'delivery_method',
        'subtotal',
        'shipping_cost',
        'total_price',
        'notes',
        'description',
        'status',
        'payment_status',
        'paid_amount',
        'remaining_amount',
        'user_id',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function testimonial()
    {
        return $this->hasOne(Testimonial::class);
    }

    public function statusHistory()
    {
        return $this->hasMany(StatusHistory::class)->orderBy('created_at', 'asc');
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public static function generateOrderCode(): string
    {
        do {
            $code = 'DC' . date('Ymd') . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (self::where('order_code', $code)->exists());

        return $code;
    }
}