<?php

namespace App\Models\Order;

use App\Models\Product\Product;
use App\Models\Product\Sku;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $table = 'order_items';

    protected $fillable = [
        'amount', 'price', 'rating', 'review', 'reviewed_at',
    ];

    protected $dates = [
        'reviewed_at',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productSku()
    {
        return $this->belongsTo(Sku::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
