<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'title',
        'description',
        'image',
        'status',
        'rating',
        'sold_count',
        'review_count',
        'price',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function skus()
    {
        return $this->hasMany(ProductSku::class);
    }
}
