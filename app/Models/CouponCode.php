<?php

namespace App\Models;

use App\Exceptions\CouponCodeUnavailableException;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CouponCode extends Model
{
    // 用常量的方式定义支持的优惠卷类型
    const TYPE_FIXED = 'fixed';
    const TYPE_PERCENT = 'percent';

    public static $typeMap = [
        self::TYPE_FIXED => '固定金额',
        self::TYPE_PERCENT => '比例',
    ];

    protected $fillable = [
        'name',
        'code',
        'type',
        'value',
        'total',
        'used',
        'min_amount',
        'start_time',
        'end_time',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean'
    ];

    protected $dates = [
        'start_time',
        'end_time',
    ];

    public static function findAvailableCode($length = 16)
    {
        do {
            // 生成一个指定长度的随机字符串，并转成大写
            $code = strtoupper(Str::random($length));
        } while (self::query()->where('code',$code)->exists());

        return $code;
    }

    protected $appends = ['description'];

    public function getDescriptionAttribute()
    {
        $str = '';

        if ($this->min_amount > 0) {
            $str = '满'.$this->min_amount;
        }

        if ($this->type === self::TYPE_PERCENT) {
            return $str.'打'.($this->value/100).'折';
        }

        return $str.'减'.$this->value;
    }

    public function checkAvailable($orderAmount = null)
    {
        if (!$this->status) {
            throw new CouponCodeUnavailableException('优惠卷不存在');
        }

        if ($this->total - $this->used <= 0) {
            throw new CouponCodeUnavailableException('该优惠券已被兑完');
        }

        if ($this->start_time && $this->start_time->gt(Carbon::now())) {
            throw new CouponCodeUnavailableException('该优惠券现在还不能使用');
        }

        if ($this->end_time && $this->end_time->lt(Carbon::now())) {
            throw new CouponCodeUnavailableException('该优惠券已过期');
        }

        if (!is_null($orderAmount) && $orderAmount < $this->min_amount) {
            throw new CouponCodeUnavailableException('订单金额不满足该优惠券最低金额');
        }
    }

    public function getAdjustedPrice($orderAmount)
    {
        // 固定金额
        if ($this->type === self::TYPE_FIXED) {
           // 为了系统的健壮性， 订单的金额最少为0.01
            return max(0.01, $orderAmount - $this->value);
        }

        return number_format($orderAmount * (100 - $this->value) / 100, 2, '.', '');
    }

    public function changeUsed($increase = true)
    {
        if ($increase) {
            return $this->where('id', $this->id)->where('used', '<', $this->total)->increment('used');
        } else {
            return $this->decrement('used');
        }
    }
}
