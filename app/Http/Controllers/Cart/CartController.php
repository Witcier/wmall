<?php

namespace App\Http\Controllers\Cart;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddCartRequest;
use App\Models\Product\Sku;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function index(Request $request)
    {
        $addresses = $request->user()->addresses()->orderBy('last_used_at', 'desc')->get();
        $cartItems = $this->cartService->get();

        return view('cart.index', [
            'cartItems' => $cartItems,
            'addresses' => $addresses,
        ]);
    } 

    public function add(AddCartRequest $request)
    {
        $skuId = $request->input('sku_id');
        $amount = $request->input('amount');

        $this->cartService->add($skuId, $amount);

        return [];
    }

    public function remove(Sku $sku, Request $request)
    {
        $this->cartService->remove($sku->id);

        return [];
    }
}
