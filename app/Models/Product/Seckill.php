<?php

namespace App\Models\Product;

use Carbon\Carbon;
use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seckill extends Model
{
    use HasFactory, HasDateTimeFormatter;

    protected $table = 'seckill_products';

    protected $fillable = [
        'start_at', 'end_at',
    ];

    protected $dates = [
        'start_at', 'end_at',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getIsBeforeStartAttribute()
    {
        return Carbon::now()->lt($this->start_at);
    }

    public function getIsAfterEndAttribute()
    {
        return Carbon::now()->gt($this->end_at);
    }
}
