<?php

namespace App\Http\Controllers\Coupon;

use App\Http\Controllers\Controller;
use App\Models\Coupon\Code;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CodesController extends Controller
{
    public function show($code, Request $request)
    {
        if (!$record = Code::where('code', $code)->first()) {
            abort(404);
        }

        $record->checkAvailable($request->user());

        return $record;
    }
}
