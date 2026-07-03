<?php

namespace App\Livewire\Tickets;

use App\Enums\Role;
use App\Models\Client;
use App\Models\Ticket;
use App\Services\TicketQueryService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class TicketTable extends Component
{
    use WithPagination;

    /**
     * Search query string.
     */
    public string $search = '';

    /**
     * Status filter value ('', 'open', 'in_progress', 'resolved', 'closed').
     */
    public string $statusFilter = '';

    /**
     * Priority filter value ('', 'low', 'medium', 'high').
     */
    public string $priorityFilter = '';

    /**
     * Client filter value for Admin ('', client_id).
     */
    public string $clientFilter = '';

    /**
     * Client companies for Admin dropdown list.
     */
    public $clients = [];

    /**
     * URL parameters mapping.
     *
     * @var array<string, array<string, string>>
     */
    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'priorityFilter' => ['except' => ''],
        'clientFilter' => ['except' => ''],
    ];

    /**
     * Mount the component and authorize access.
     */
    public function mount(): void
    {
        Gate::authorize('viewAny', Ticket::class);

        // Preload clients list for Admin filter dropdown
        if (auth()->user()->role === Role::Admin) {
            $this->clients = Client::orderBy('name')->get();
        }
    }

    /**
     * Reset pagination when filters change.
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPriorityFilter(): void
    {
        $this->resetPage();
    }

    public function updatingClientFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Render the component view.
     *
     * @return View
     */
    public function render(TicketQueryService $queryService)
    {
        Gate::authorize('viewAny', Ticket::class);

        $user = auth()->user();

        $filters = [
            'search' => $this->search,
            'status' => $this->statusFilter,
            'priority' => $this->priorityFilter,
            'client_id' => $user->role === Role::Admin ? $this->clientFilter : null,
        ];

        // Fetch scoped & filtered query, eager-loading relationships
        $tickets = $queryService->filteredQuery($user, $filters)
            ->with(['client', 'assignedEngineer'])
            ->latest()
            ->paginate(10);

        return view('livewire.tickets.ticket-table', [
            'tickets' => $tickets,
        ]);
    }
}
