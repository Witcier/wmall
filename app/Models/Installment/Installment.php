<?php

namespace App\Models\Installment;

use App\Models\Order\Order;
use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Installment extends Model
{
    use HasFactory;

    const STATUS_PENDING = 1;
    const STATUS_REPAYING = 2;
    CONST STATUS_FINISHED = 3;

    public static $statusMap = [
        self::STATUS_PENDING => '未执行',
        self::STATUS_REPAYING => '还款中',
        self::STATUS_FINISHED => '还款完成',
    ];

    protected $fillable = [
        'no', 'total_amount', 'count', 'fee_rate', 'fine_rate', 'status'
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

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function items()
    {
        return $this->hasMany(Item::class);
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

        \Log::warning("find installment no failed");

        return false;
    }

    public static function getFirstDueAt()
    {
        return Carbon::create(null, null, 9, 23, 59, 59)->copy()->addMonth();
    }
}
