<?php

namespace App\Http\Controllers;

use App\Exceptions\CouponCodeUnavailableException;
use Illuminate\Http\Request;
use App\Models\CouponCode;
use Carbon\Carbon;

class CouponCodesController extends Controller
{
    public function show($code, Request $request)
    {
        // 判断优惠卷是否存在
        if (!$record = CouponCode::where('code',$code)->first()) {
            throw new CouponCodeUnavailableException('优惠卷不存在');
        }

        $record->checkAvailable($request->user());

        return $record;
    }
}
