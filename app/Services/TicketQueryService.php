<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class TicketQueryService
{
    /**
     * Returns a base Eloquent query scoped to the user's role.
     */
    public function scopedQuery(User $user): Builder
    {
        return Ticket::visibleTo($user);
    }

    /**
     * Applies status, priority, client_id, and optional search filters on top of the scoped query.
     *
     * @param  array<string, mixed>  $filters
     */
    public function filteredQuery(User $user, array $filters): Builder
    {
        $query = $this->scopedQuery($user);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', '%'.$search.'%')
                    ->orWhere('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        return $query;
    }
}
