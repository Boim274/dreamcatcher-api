<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'base_price',
        'price_per_unit',
        'minimum_order',
        'thumbnail',
        'is_active',
        'pricing_type',
        'pricing_config',
        'options_config',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'price_per_unit' => 'decimal:2',
        'is_active' => 'boolean',
        'pricing_config' => 'array',
        'options_config' => 'array',
    ];

    public function portfolios()
    {
        return $this->hasMany(Portfolio::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getPricingForQuantity(string $sablonType, int $quantity): float
    {
        if ($this->pricing_type === 'flat') {
            return (float) $this->base_price;
        }

        $config = $this->pricing_config ?? [];
        $typeConfig = $config[strtolower($sablonType)] ?? [];

        if (empty($typeConfig)) {
            return (float) $this->base_price;
        }

        if ($quantity >= 24) {
            return (float) ($typeConfig['lusin_1'] ?? $this->base_price);
        } elseif ($quantity >= 12) {
            return (float) ($typeConfig['lusin_1'] ?? $this->base_price);
        } else {
            return (float) ($typeConfig['satuan'] ?? $this->base_price);
        }
    }

    public function getSablonTypePrice(string $sablonType, int $quantity): float
    {
        $config = $this->pricing_config ?? [];
        $typeConfig = $config[strtolower($sablonType)] ?? [];

        if (empty($typeConfig)) {
            return (float) $this->base_price;
        }

        if ($quantity >= 24) {
            return (float) ($typeConfig['lusin_2_6'] ?? $typeConfig['lusin_1'] ?? $this->base_price);
        } else {
            return (float) ($typeConfig['satuan'] ?? $this->base_price);
        }
    }
}
