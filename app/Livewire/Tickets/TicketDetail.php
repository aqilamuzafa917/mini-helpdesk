<?php

namespace App\Livewire\Tickets;

use App\Http\Requests\UpdateTicketRequest;
use App\Models\Ticket;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

class TicketDetail extends Component
{
    /**
     * The Ticket model instance.
     */
    public Ticket $ticket;

    /**
     * Bound fields for status updates.
     */
    public string $status = '';

    public string $status_change_notes = '';

    /**
     * Mount the component and authorize access.
     */
    public function mount(Ticket $ticket): void
    {
        Gate::authorize('view', $ticket);

        $this->ticket = $ticket;
        $this->status = $ticket->status->value;
    }

    /**
     * Update the ticket's status.
     *
     * @return void
     */
    public function updateStatus()
    {
        Gate::authorize('update', $this->ticket);

        $request = new UpdateTicketRequest;
        $payload = [
            'id' => $this->ticket->id,
            'status' => $this->status,
            'status_change_notes' => $this->status_change_notes ?: null,
            // Non-admin rules require validating existing values
            'title' => $this->ticket->title,
            'description' => $this->ticket->description,
            'priority' => $this->ticket->priority->value,
            'client_id' => $this->ticket->client_id,
            'assigned_engineer_id' => $this->ticket->assigned_engineer_id,
        ];

        $request->merge($payload);

        if (! $request->authorize()) {
            abort(403, 'Forbidden');
        }

        $this->validate($request->rules());

        // Set status change notes directly on model for TicketObserver logging
        $this->ticket->status_change_notes = $this->status_change_notes ?: null;

        $this->ticket->update([
            'status' => $this->status,
        ]);

        $this->status_change_notes = '';
        $this->ticket->refresh();

        session()->flash('status', 'Ticket status updated successfully.');
    }

    /**
     * Render the component.
     *
     * @return View
     */
    public function render()
    {
        Gate::authorize('view', $this->ticket);

        // Fetch audit history log sorted latest first
        $histories = $this->ticket->statusHistories()
            ->with('changer')
            ->orderBy('changed_at', 'desc')
            ->get();

        return view('livewire.tickets.ticket-detail', [
            'histories' => $histories,
        ]);
    }
}
