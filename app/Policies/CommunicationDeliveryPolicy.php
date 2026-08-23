<?php

namespace App\Policies;

use App\Models\CommunicationDelivery;
use App\Models\User;

class CommunicationDeliveryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasExactRoles(['admin']) && $user->can('view communication deliveries');
    }

    public function view(User $user, CommunicationDelivery $delivery): bool
    {
        return $this->viewAny($user);
    }

    public function manage(User $user, CommunicationDelivery $delivery): bool
    {
        return $user->hasExactRoles(['admin']) && $user->can('manage communication deliveries');
    }
}
