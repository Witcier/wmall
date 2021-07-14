<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AddressesController extends Controller
{
    public function index(Request $request)
    {
        return view('users.addresses.index', [
            'addresses' => $request->user()->addresses,
        ]);
    }
}
