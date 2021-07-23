<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\Request;
use App\Models\Product\Sku;
use Illuminate\Validation\Rule;

class OrderRequest extends Request
{
    public function rules()
    {
        return [
            'address_id' => [
                'required',
                Rule::exists('user_addresses', 'id')->where('user_id', $this->user()->id),
            ],
            'items' => [
                'required', 'array',
            ],
            'items.*.sku_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!$sku = Sku::find($value)) {
                        return $fail('商品不存在');
                    }
                    if (!$sku->product->on_sale) {
                        return $fail('商品不存在');
                    }
                    if ($sku->stock == 0) {
                        return $fail('商品已售完');
                    }
                    
                    preg_match('/items\.(\d+)\.sku_id/', $attribute, $m);
                    $index = $m[1];

                    $amount = $this->input('items')[$index]['amount'];
                    if ($amount > 0 && $amount > $sku->stock) {
                        return $fail('商品库存不足');
                    }
                } 
            ],
            'items.*.amount' => [
                'required', 'integer', 'min:1',
            ],
        ];
    }
}
