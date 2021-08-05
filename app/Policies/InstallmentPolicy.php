<?php

namespace App\Policies;

use App\Models\Installment\Installment;
use App\Models\User\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InstallmentPolicy
{
    use HandlesAuthorization;

    public function own(User $user, Installment $installment)
    {
        return $installment->user_id == $user->id;
    }
}
