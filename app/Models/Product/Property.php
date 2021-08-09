<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    protected $table = 'product_properties';

    protected $fillable = [
        'name', 'value',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
