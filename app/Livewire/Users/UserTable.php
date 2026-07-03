<?php

namespace App\Livewire\Users;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class UserTable extends Component
{
    use WithPagination;

    /**
     * Search query string.
     */
    public string $search = '';

    /**
     * Filter by role ('', 'admin', 'engineer', 'client').
     */
    public string $roleFilter = '';

    /**
     * Filter by active status ('', 'active', 'inactive').
     */
    public string $statusFilter = '';

    /**
     * URL parameters mapping.
     *
     * @var array<string, array<string, string>>
     */
    protected $queryString = [
        'search' => ['except' => ''],
        'roleFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    /**
     * Mount the component and authorize access.
     */
    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
    }

    /**
     * Toggle status (active/inactive) for a user.
     */
    public function toggleStatus(User $user): void
    {
        Gate::authorize('update', $user);

        // Prevent self-deactivation to avoid locking the current admin out.
        if (auth()->id() === $user->id) {
            session()->flash('error', 'You cannot deactivate your own account.');

            return;
        }

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        session()->flash('status', 'User status updated successfully.');
    }

    /**
     * Reset pagination when filters change.
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

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
        Gate::authorize('viewAny', User::class);

        $query = User::with('client');

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            });
        }

        if (! empty($this->roleFilter)) {
            $query->where('role', $this->roleFilter);
        }

        if ($this->statusFilter !== '') {
            $query->where('is_active', $this->statusFilter === 'active');
        }

        return view('livewire.users.user-table', [
            'users' => $query->latest()->paginate(10),
        ]);
    }
}
