<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\CouponCode;
use Faker\Generator as Faker;

$factory->define(CouponCode::class, function (Faker $faker) {
    // 首先随机取得一个类型
    $type = $faker->randomElement(array_keys(CouponCode::$typeMap));
    // 根据取得的类型生成相对应的折扣
    $value = $type === CouponCode::TYPE_FIXED ? random_int(1,200) : random_int(1,50);

    // 如果是固定金额，则最低订单金额必须必优惠金额高 0.01 
    if ($type === CouponCode::TYPE_FIXED) {
        $minAmount = $value + 0.01;
    } else {
        // 如果是折扣优惠卷，有 50% 概率不需要最低订单金额
        if (random_int(0,100)) {
            $minAmount = 0;
        } else {
            $minAmount = random_int(100,1000);
        }
    }

    return [
        'name' => join(' ', $faker->words),
        'code' => CouponCode::findAvailableCode(),
        'type' => $type,
        'value' => $value,
        'total' => 100,
        'used'  => random_int(1,100),
        'min_amount' => $minAmount,
        'start_time' => null,
        'end_time' => null,
        'status' => true,
    ];
});
