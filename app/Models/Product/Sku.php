<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sku extends Model
{
    use HasFactory;

    protected $table = 'product_skus';

    protected $fillable = [
        'title', 'description', 'stock', 'price'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
