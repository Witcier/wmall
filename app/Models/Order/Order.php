<?php

namespace App\Models\Order;

use App\Models\Coupon\Code;
use App\Models\User\User;
use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class Order extends Model
{
    use HasFactory, HasDateTimeFormatter;

    // 退款状态
    const REFUND_STATUS_PENDING = 0;
    const REFUND_STATUS_APPLIED = 1;
    const REFUND_STATUS_PROCESSING = 2;
    const REFUND_STATUS_SUCCESS = 3;
    const REFUND_STATUS_FAILED = 4;

    // 物流状态
    const SHIP_STATUS_PENDING = 0;
    const SHIP_STATUS_DELIVERED = 1;
    const SHIP_STATUS_RECEIVED = 2;

    // 支付方式
    const PAYMENT_METHOD_ALIPAY = 1;
    const PAYMENT_METHOD_WECHAT = 2;
    const PAYMENT_METHOD_INSTALLMENT = 3;

    const TYPE_NORMAL = 1;
    const TYPE_CROWDFUNDING = 2;
    const TYPE_SECKILL = 3;

    public static $refundStatusMap = [
        self::REFUND_STATUS_PENDING => '未退款',
        self::REFUND_STATUS_APPLIED => '已申请退款',
        self::REFUND_STATUS_PROCESSING => '退款中',
        self::REFUND_STATUS_SUCCESS => '退款成功',
        self::REFUND_STATUS_FAILED => '退款失败',
    ];

    public static $shipStatusMap = [
        self::SHIP_STATUS_PENDING => '未发货',
        self::SHIP_STATUS_DELIVERED => '发货中',
        self::SHIP_STATUS_RECEIVED => '已收货',
    ];

    public static $paymentMethodMap = [
        self::PAYMENT_METHOD_ALIPAY => '支付宝支付',
        self::PAYMENT_METHOD_WECHAT => '微信支付',
        self::PAYMENT_METHOD_INSTALLMENT => '分期付款',
    ];

    public static $typeMap = [
        self::TYPE_NORMAL       => '普通订单',
        self::TYPE_CROWDFUNDING => '众筹订单',
        self::TYPE_SECKILL      => '秒杀订单',
    ];

    protected $fillable = [
        'no', 'address', 'total_amount', 'remark', 'paid', 'paid_at', 'payment_method', 'payment_no', 'refund_status', 'refund_no', 'closed', 'reviewed', 'ship_status',
        'ship_data', 'extra', 'type',
    ];

    protected $casts = [
        'closed' => 'boolean',
        'paid' => 'boolean',
        'reviewed' => 'boolean',
        'address' => 'json',
        'ship_data' => 'json',
        'extra' => 'json',
    ];

    protected $dates = [
        'paid_at',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->no) {
                $model->no = static::findAvailableNo();

                if (!$model->no) {
                    return false;
                }
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }

    public function couponCode()
    {
        return $this->belongsTo(Code::class);
    }

    public static function findAvailableNo()
    {
        $prefix = date('YmdHis');
        for ($i = 0; $i < 10; $i++) {
            $no = $prefix . str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            if (!static::query()->where('no', $no)->exists()) {
                return $no;
            }
        }

        \Log::warning("find order no failed");

        return false;
    }

    public static function findAvailableRefundNo()
    {
        do {
            $no = Uuid::uuid4()->getHex();
        } while (self::query()->where('refund_no', $no)->exists());

        return $no;
    }
}
