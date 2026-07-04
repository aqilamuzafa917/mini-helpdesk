<?php

namespace App\Livewire\Clients;

use App\Enums\ClientStatus;
use App\Models\Client;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ClientTable extends Component
{
    use WithPagination;

    /**
     * Search term for filtering clients.
     */
    public string $search = '';

    /**
     * Status filter value ('', 'active', 'inactive').
     */
    public string $statusFilter = '';

    /**
     * Query string parameters to sync with the URL.
     *
     * @var array<string, array<string, string>>
     */
    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    /**
     * Handle component initialization and authorization.
     */
    public function mount(): void
    {
        Gate::authorize('viewAny', Client::class);
    }

    /**
     * Toggle the status of a specific client between Active and Inactive.
     */
    public function toggleStatus(Client $client): void
    {
        Gate::authorize('update', $client);

        $newStatus = $client->status === ClientStatus::Active
            ? ClientStatus::Inactive
            : ClientStatus::Active;

        $client->update(['status' => $newStatus]);

        session()->flash('status', 'Client status updated successfully.');
    }

    /**
     * Reset pagination when search query updates.
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination when status filter updates.
     */
    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Render the component view.
     *
     * @return View
     */
    public function render()
    {
        Gate::authorize('viewAny', Client::class);

        $query = Client::query();

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('contact_person', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%')
                    ->orWhere('phone', 'like', '%'.$this->search.'%');
            });
        }

        if (! empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        return view('livewire.clients.client-table', [
            'clients' => $query->latest()->paginate(10),
        ]);
    }
}
