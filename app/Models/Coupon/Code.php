<?php

namespace App\Models\Coupon;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Code extends Model
{
    use HasFactory, HasDateTimeFormatter;

    protected $table = 'coupon_codes';

    const TYPE_FIXED = 1;
    const TYPE_PERCENT = 2;

    public static $typeMap = [
        self::TYPE_FIXED => '固定比例',
        self::TYPE_PERCENT => '折扣',
    ];

    protected $fillable = [
        'name', 'code', 'type', 'value', 'total', 'used', 'min_amount', 'start_at', 'end_at', 'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    protected $dates = [
        'start_at', 'end_at',
    ];

    protected $appends = [
        'description',
    ];

    public static function findAvailableCode($length = 10)
    {
        do {
            $code = strtoupper(Str::random($length));
        } while (self::query()->where('code', $code)->exists());

        return $code;
    }

    public function getDescriptionAttribute()
    {
        $str = '';

        if ($this->min_amount > 0) {
            $str = '满' . $this->min_amount;
        }

        if ($this->type === self::TYPE_PERCENT) {
            return $str . '打' . $this->value / 100 . '折';
        }

        return $str . '减' . $this->value;
    }
}
