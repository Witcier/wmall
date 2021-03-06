<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Installment extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_REPAYING = 'repaying';
    const STATUS_FINISHED = 'finished';

    public static $statusMap = [
        self::STATUS_PENDING => '未执行',
        self::STATUS_REPAYING => '还款中',
        self::STATUS_FINISHED => '已完成',
    ];

    protected $fillable = [
        'no',
        'total_amount',
        'count',
        'fee_rate',
        'fine_rate',
        'status',
    ];

    protected static function boot()
    {
        parent::boot();
        // 创建模型监听事件，在写入数据库前触发
        static::creating(function ($model) {
            // 如果模型的 no 字段为空
            if (!$model->no) {
                // 调用 findAvailableNo 生成分期流水号
                $model->no = static::findAvailableNo();
            }
            // 生成分期流水号失败，则停止
            if (!$model->no) {
                return false;
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function items()
    {
        return $this->hasMany(InstallmentItem::class);
    }

    public static function findAvailableNo()
    {
        // 分期流水号前缀
        $prefix = 'WI'.date('YmdHis');
        for ($i=0; $i < 10; $i++) {
           // 随机生成 10 位的数字
           $no = $prefix.str_pad(random_int(0,9999999999),10,'0',STR_PAD_LEFT);
           // 判断是否存
           if (!static::query()->where('no',$no)->exists()) {
               return $no;
           }
        }
        \Log::warning('find installment no failed');

        return false;
    }

    public function refreshRefundStatus()
    {
        $allSuccess = true;
        // 重新加载 items ， 保证与数据库中数据同步
        $this->load(['items']);
        foreach ($this->items as $item) {
            if ($item->paid_at && $item->refund_status !== InstallmentItem::REFUND_STATUS_SUCCESS) {
                $allSuccess = false;
                break;
            }
        }

        if ($allSuccess) {
            $this->order->update([
                'refund_status' => Order::REFUND_STATUS_SUCCESS,
            ]);
        }
    }
}
