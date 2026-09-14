<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
        'role',
        'phone',
        'address',
        'generate_used',
        'generate_limit',
        'edit_used',
        'edit_limit',
        'last_generate_at',
        'last_edit_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'generate_used' => 'integer',
            'generate_limit' => 'integer',
            'edit_used' => 'integer',
            'edit_limit' => 'integer',
            'last_generate_at' => 'datetime',
            'last_edit_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function testimonial()
    {
        return $this->hasOne(Testimonial::class);
    }

    public function verifiedPayments()
    {
        return $this->hasMany(Payment::class, 'verified_by');
    }
}