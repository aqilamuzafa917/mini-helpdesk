<?php

namespace App\Livewire\Tickets;

use App\Enums\Priority;
use App\Enums\Role;
use App\Enums\TicketStatus;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Client;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

class TicketForm extends Component
{
    /**
     * The Ticket model instance being created or edited.
     */
    public ?Ticket $ticket = null;

    /**
     * Indicates if the component is in edit mode.
     */
    public bool $isEdit = false;

    /**
     * Bound input fields.
     */
    public string $title = '';

    public string $description = '';

    public string $priority = 'medium';

    public string $status = 'open';

    public ?int $client_id = null;

    public ?int $assigned_engineer_id = null;

    public string $status_change_notes = '';

    /**
     * Dropdowns lists.
     */
    public $clients = [];

    public $engineers = [];

    /**
     * Mount the component and initialize bindings.
     */
    public function mount(?Ticket $ticket = null): void
    {
        $user = auth()->user();

        // Admin needs list of clients and engineers for dropdowns
        if ($user->role === Role::Admin) {
            $this->clients = Client::orderBy('name')->get();
            $this->engineers = User::where('role', Role::Engineer)->orderBy('name')->get();
        }

        if ($ticket && $ticket->exists) {
            Gate::authorize('update', $ticket);
            $this->ticket = $ticket;
            $this->isEdit = true;

            $this->title = $ticket->title;
            $this->description = $ticket->description;
            $this->priority = $ticket->priority->value;
            $this->status = $ticket->status->value;
            $this->client_id = $ticket->client_id;
            $this->assigned_engineer_id = $ticket->assigned_engineer_id;
        } else {
            Gate::authorize('create', Ticket::class);
            $this->priority = Priority::Medium->value;
            $this->status = TicketStatus::Open->value;

            // Clients automatically get their own client association
            if ($user->role === Role::Client) {
                $this->client_id = $user->client_id;
            }
        }
    }

    /**
     * Save the ticket record (create or update).
     *
     * @return RedirectResponse
     */
    public function save()
    {
        $user = auth()->user();

        if ($this->isEdit) {
            Gate::authorize('update', $this->ticket);
            $request = new UpdateTicketRequest;

            // Prepare update request validation payload
            $payload = [
                'id' => $this->ticket->id,
                'status' => $this->status,
                'status_change_notes' => $this->status_change_notes ?: null,
                'title' => $this->title,
                'description' => $this->description,
                'priority' => $this->priority,
                'client_id' => $this->client_id,
                'assigned_engineer_id' => $this->assigned_engineer_id,
            ];

            $request->merge($payload);

            if (! $request->authorize()) {
                abort(403, 'Forbidden');
            }

            $this->validate($request->rules());

            // Build Eloquent update attributes array
            $data = [
                'status' => $this->status,
            ];

            // Attach notes property directly on model for TicketObserver tracking
            $this->ticket->status_change_notes = $this->status_change_notes ?: null;

            if ($user->role === Role::Admin) {
                $data = array_merge($data, [
                    'title' => $this->title,
                    'description' => $this->description,
                    'priority' => $this->priority,
                    'client_id' => $this->client_id,
                    'assigned_engineer_id' => $this->assigned_engineer_id,
                ]);
            }

            $this->ticket->update($data);
            session()->flash('status', 'Ticket updated successfully.');
        } else {
            Gate::authorize('create', Ticket::class);
            $request = new StoreTicketRequest;

            $payload = [
                'title' => $this->title,
                'description' => $this->description,
                'priority' => $this->priority,
                'status' => $this->status,
                'client_id' => $user->role === Role::Admin ? $this->client_id : $user->client_id,
                'assigned_engineer_id' => $user->role === Role::Admin ? $this->assigned_engineer_id : null,
            ];

            $request->merge($payload);
            $this->validate($request->rules());

            $ticketModel = new Ticket([
                'title' => $this->title,
                'description' => $this->description,
                'priority' => $this->priority,
                'status' => $this->status,
                'client_id' => $user->role === Role::Admin ? $this->client_id : $user->client_id,
                'assigned_engineer_id' => $user->role === Role::Admin ? $this->assigned_engineer_id : null,
                'created_by' => $user->id,
            ]);

            // Set notes directly on model for TicketObserver Audit Trail
            $ticketModel->status_change_notes = 'Ticket created.';
            $ticketModel->save();

            session()->flash('status', 'Ticket created successfully.');
        }

        return redirect()->route('tickets.index');
    }

    /**
     * Render the component.
     *
     * @return View
     */
    public function render()
    {
        return view('livewire.tickets.ticket-form');
    }
}
