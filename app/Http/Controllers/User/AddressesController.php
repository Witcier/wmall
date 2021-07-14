<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\AddressRequest;
use App\Models\User\Address;
use Illuminate\Http\Request;

class AddressesController extends Controller
{
    public function index(Request $request)
    {
        return view('users.addresses.index', [
            'addresses' => $request->user()->addresses,
        ]);
    }

    public function create()
    {
        return view('users.addresses.post', [
            'address' => new Address(),
        ]);
    }

    public function store(AddressRequest $request)
    {
        $request->user()->addresses()->create($request->only([
            'province', 'city', 'district', 'address', 'zip', 'contact_name', 'contact_phone',
        ]));

        return redirect()->route('user.addresses.index');
    }

    public function edit(Address $address)
    {
        $this->authorize('own', $address);

        return view('users.addresses.post', [
            'address' => $address,
        ]);
    }

    public function update(Address $address, AddressRequest $request)
    {
        $this->authorize('own', $address);

        $address->update($request->only([
            'province', 'city', 'district', 'address', 'zip', 'contact_name', 'contact_phone',
        ]));

        return redirect()->route('user.addresses.index');
    }

    public function destroy(Address $address)
    {
        $this->authorize('own', $address);

        $address->delete();

        return [];
    }
}
