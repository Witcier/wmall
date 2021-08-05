<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\Request;
use App\Models\Product\Crowdfunding;
use App\Models\Product\Product;
use App\Models\Product\Sku;
use Illuminate\Validation\Rule;

class CrowdfundingOrderRequest extends Request
{
    public function rules()
    {
        return [
            'sku_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!$sku = Sku::find($value)) {
                        return $fail('商品不存在');
                    }
                    if (!$sku->product->on_sale) {
                        return $fail('商品不存在');
                    }
                    if ($sku->product->type !== Product::TYPE_CROWDFUNDING) {
                        return $fail('商品不支持众筹');
                    }
                    if ($sku->product->crowdfunding->status !== Crowdfunding::STATUS_FUNDING) {
                        return $fail('商品众筹已结束');
                    }
                    if ($sku->stock === 0) {
                        return $fail('商品已售完');
                    }
                    if ($this->input('amount') > 0 && $sku->stock < $this->input('amount')) {
                        return $fail('商品库存不足');
                    }
                } 
            ],
            'amount' => [
                'required', 'integer', 'min:1'
            ],
            'address_id' => [
                'required',
                Rule::exists('user_addresses', 'id')->where('user_id', $this->user()->id),
            ],
        ];
    }
}
