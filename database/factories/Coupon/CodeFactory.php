<?php

namespace Database\Factories\Coupon;

use App\Models\Coupon\Code;
use Illuminate\Database\Eloquent\Factories\Factory;

class CodeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Code::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // 首先随机取得一个类型
        $type  = $this->faker->randomElement(array_keys(Code::$typeMap));
        // 根据取得的类型生成对应折扣
        $value = $type === Code::TYPE_FIXED ? random_int(1, 200) : random_int(1, 50);

        // 如果是固定金额，则最低订单金额必须要比优惠金额高 0.01 元
        if ($type === Code::TYPE_FIXED) {
            $minAmount = $value + 0.01;
        } else {
            // 如果是百分比折扣，有 50% 概率不需要最低订单金额
            if (random_int(0, 100) < 50) {
                $minAmount = 0;
            } else {
                $minAmount = random_int(100, 1000);
            }
        }

        return [
            'name'       => join(' ', $this->faker->words), // 随机生成名称
            'code'       => Code::findAvailableCode(), // 调用优惠码生成方法
            'type'       => $type,
            'value'      => $value,
            'total'      => 1000,
            'used'       => 0,
            'min_amount' => $minAmount,
            'start_at' => null,
            'end_at'  => null,
            'status'    => true,
        ];
    }
}
