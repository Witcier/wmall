<?php

namespace App\Models\Installment;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $table = 'installment_items';

    const REFUND_STATUS_PENDING = 1;
    const REFUND_STATUS_PROCESSING = 2;
    const REFUND_STATUS_SUCCESS = 3;
    const REFUND_STATUS_FAILED = 4;

    public static $refundStatusMap = [
        self::REFUND_STATUS_PENDING => '未退款',
        self::REFUND_STATUS_PROCESSING => '退款中',
        self::REFUND_STATUS_SUCCESS => '退款成功',
        self::REFUND_STATUS_FAILED => '退款失败',
    ];

    protected $fillable = [
        'sequence', 'base', 'fee', 'fine', 'due_at', 'paid', 'paid_at', 'payment_method', 'payment_no', 'refund_status',
    ];

    protected $dates = [
        'due_at', 'paid_at',
    ];

    public function installment()
    {
        return $this->belongsTo(Installment::class);
    }

    public function getTotalAttribute()
    {
        $total = big_number($this->base)->add($this->fee);
        if (!is_null($this->fine)) {
            $total->add($this->fine);
        }

        return $total->getValue();
    }

    public function getIsOverDueAttribute()
    {
        return Carbon::now()->gt($this->due_at);
    }
}
