<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Ticket;
use App\Models\User;

class TicketCommentPolicy
{
    /**
     * Determine if a comment can be created on a ticket.
     */
    public function create(User $user, Ticket $ticket, bool $isInternal = false): bool
    {
        // Check ticket visibility
        $ticketPolicy = new TicketPolicy;
        if (! $ticketPolicy->view($user, $ticket)) {
            return false;
        }

        return match ($user->role) {
            Role::Admin => true,
            Role::Engineer => true, // Engineers can add public or internal comments on assigned tickets
            Role::Client => ! $isInternal, // Clients can only add public comments (is_internal = false)
        };
    }
}
