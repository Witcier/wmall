<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CouponCode;
use Carbon\Carbon;

class CouponCodesController extends Controller
{
    public function show($code)
    {
        // 判断优惠卷是否存在
        if (!$record = CouponCode::where('code',$code)->first()) {
            abort(404);
        }

        // 判断哟慧娟是否启用
        if (!$record->status) {
            abort(404);
        }

        if ($record->total - $record->used <= 0) {
            return response()->json(['msg' => '该优惠卷已经被领完了'], 403);
        }

        if ($record->start_time && $record->start_time->gt(Carbon::now())) {
            return response()->json(['msg' => '该优惠卷现在还不能使用'], 403);
        }

        if ($record->end_time && $record->end_time->lt(Carbon::now())) {
            return response()->json(['msg' => '该优惠卷已过期'], 403);
        }

        return $record;
    }
}
