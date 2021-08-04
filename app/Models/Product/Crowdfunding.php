<?php

namespace App\Models\Product;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Crowdfunding extends Model
{
    use HasFactory, HasDateTimeFormatter;

    protected $table = 'crowdfunding_products';

    const STATUS_FUNDING = 1;
    const STATUS_SUCCESS = 2;
    const STATUS_FAIL = 3;

    public static $statusMap = [
        self::STATUS_FUNDING => '众筹中',
        self::STATUS_SUCCESS => '众筹成功',
        self::STATUS_FAIL    => '众筹失败',
    ];

    protected $fillable = [
        'total_amount', 'target_amount', 'user_count', 'status', 'end_at'
    ];

    protected $dates = [
        'end_at',
    ];

    public $timestamps = false;

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getPercentAttribute()
    {
        $value = $this->attributes['total_amount'] / $this->attributes['target_amount'];

        return floatval(number_format($value * 100, 2, '.', ''));
    }
}
