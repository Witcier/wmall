<?php

namespace App\Models\Coupon;

use App\Exceptions\CouponCodeUnavailableException;
use App\Models\Order\Order;
use App\Models\User\User;
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
            return $str . '打' . $this->value / 10 . '折';
        }

        return $str . '减' . $this->value;
    }

    public function checkAvailable(User $user,$orderAmount = null)
    {
        if (!$this->status) {
            throw new CouponCodeUnavailableException('优惠券不存在');
        }

        if ($this->total - $this->used <= 0) {
            throw new CouponCodeUnavailableException('该优惠券已被兑完');
        }

        if ($this->start_at && $this->start_at->gt(Carbon::now())) {
            throw new CouponCodeUnavailableException('该优惠券现在还不能使用');
        }

        if ($this->end_at && $this->end_at->lt(Carbon::now())) {
            throw new CouponCodeUnavailableException('该优惠券已过期');
        }

        if (!is_null($orderAmount) && $orderAmount < $this->min_amount) {
            throw new CouponCodeUnavailableException('订单金额不满足该优惠券最低金额');
        }

        $used = Order::where('user_id', $user->id)
            ->where('coupon_code_id', $this->id)
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('paid', true)
                    ->where('closed', false);
                })->orWhere(function ($query) {
                    $query->where('paid', true)
                        ->where('refund_status', '!=', Order::REFUND_STATUS_SUCCESS);
                });
                
            })
            ->exists();

        if ($used) {
            throw new CouponCodeUnavailableException('你已经使用过该优惠卷了');
        }
    }

    public function getAdjustedPrice($orderAmount)
    {
        if ($this->type === self::TYPE_FIXED) {
            return max(0.01, $orderAmount - $this->value);
        }

        return number_format($orderAmount * (100 - $this->value) / 100, 2, '.', '');
    }

    public function changeUsed($increase = true)
    {
        if ($increase) {
            return $this->where('id', $this->id)->where('used', '<', $this->total)->increment('used');
        }

        return $this->decrement('used');
    }
}
