<?php

namespace App\Observers;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use App\Services\TicketNumberService;
use Exception;
use Illuminate\Support\Facades\Auth;

class TicketObserver
{
    public function __construct(protected TicketNumberService $ticketNumberService) {}

    /**
     * Handle the Ticket "creating" event.
     */
    public function creating(Ticket $ticket): bool
    {
        try {
            $number = $this->ticketNumberService->generate();
            if (empty($number)) {
                return false;
            }
            $ticket->ticket_number = $number;

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Handle the Ticket "created" event.
     */
    public function created(Ticket $ticket): void
    {
        // Write initial status history record (null -> open)
        TicketStatusHistory::create([
            'ticket_id' => $ticket->id,
            'old_status' => null,
            'new_status' => $ticket->status->value,
            'notes' => $ticket->status_change_notes ?? 'Ticket created.',
            'changed_by' => Auth::id() ?? $ticket->created_by,
            'changed_at' => now(),
        ]);
    }

    /**
     * Handle the Ticket "updating" event.
     *
     * Only mutate model attributes here (resolved_at). Audit history is written
     * in updated() so it only records changes that were actually persisted.
     */
    public function updating(Ticket $ticket): void
    {
        if ($ticket->isDirty('status')) {
            $newStatus = $ticket->status;
            $oldStatus = $ticket->getOriginal('status');

            // Manage resolved_at timestamp
            if ($newStatus === TicketStatus::Resolved) {
                $ticket->resolved_at = now();
            } elseif ($oldStatus === TicketStatus::Resolved) {
                $ticket->resolved_at = null;
            }

            // Stash the pre-update status so updated() can reference it after
            // the dirty state is cleared by Eloquent.
            $ticket->previousStatus = $oldStatus;
        }
    }

    /**
     * Handle the Ticket "updated" event.
     *
     * Writing history here guarantees the database row was successfully updated
     * before we create the audit record.
     */
    public function updated(Ticket $ticket): void
    {
        if (isset($ticket->previousStatus)) {
            $oldStatus = $ticket->previousStatus;

            TicketStatusHistory::create([
                'ticket_id' => $ticket->id,
                'old_status' => $oldStatus instanceof TicketStatus ? $oldStatus->value : $oldStatus,
                'new_status' => $ticket->status->value,
                'notes' => $ticket->status_change_notes ?? null,
                'changed_by' => Auth::id(),
                'changed_at' => now(),
            ]);

            unset($ticket->previousStatus);
        }
    }
}
