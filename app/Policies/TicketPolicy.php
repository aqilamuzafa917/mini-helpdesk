<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return match ($user->role) {
            Role::Admin => true,
            Role::Engineer => $ticket->assigned_engineer_id === $user->id,
            Role::Client => $ticket->client_id === $user->client_id,
        };
    }

    public function create(User $user): bool
    {
        return $user->role === Role::Admin || $user->role === Role::Client;
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return match ($user->role) {
            Role::Admin => true,
            Role::Engineer => $ticket->assigned_engineer_id === $user->id,
            Role::Client => false,
        };
    }
}
