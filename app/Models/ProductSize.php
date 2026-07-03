<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSize extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'name', 'stock', 'sort_order'];

    protected $casts = ['stock' => 'integer'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
