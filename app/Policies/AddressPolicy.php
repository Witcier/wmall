<?php

namespace App\Policies;

use App\Models\User\Address;
use App\Models\User\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AddressPolicy
{
    use HandlesAuthorization;

    public function own(User $user, Address $address)
    {
        return $address->user_id == $user->id;
    }
}
