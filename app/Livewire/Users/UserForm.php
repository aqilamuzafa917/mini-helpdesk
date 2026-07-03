<?php

namespace App\Livewire\Users;

use App\Enums\ClientStatus;
use App\Enums\Role;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

class UserForm extends Component
{
    /**
     * The User model instance being created or edited.
     */
    public ?User $user = null;

    /**
     * Indicates if the component is in edit mode.
     */
    public bool $isEdit = false;

    /**
     * Bound input fields.
     */
    public string $name = '';

    public string $email = '';

    public string $role = 'client';

    public ?int $client_id = null;

    public bool $is_active = true;

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Dropdown list of clients.
     */
    public $clients = [];

    /**
     * Mount the component and initialize bindings.
     */
    public function mount(?User $user = null): void
    {
        // Only load active clients for selection.
        $this->clients = Client::where('status', ClientStatus::Active)->orderBy('name')->get();

        if ($user && $user->exists) {
            Gate::authorize('update', $user);
            $this->user = $user;
            $this->isEdit = true;

            $this->name = $user->name;
            $this->email = $user->email;
            $this->role = $user->role->value;
            $this->client_id = $user->client_id;
            $this->is_active = $user->is_active;
        } else {
            Gate::authorize('create', User::class);
            $this->role = Role::Client->value;
            $this->is_active = true;
        }
    }

    /**
     * Save the user record (create or update).
     *
     * @return RedirectResponse
     */
    public function save()
    {
        // Client ID must be null if the role is not 'client'.
        $clientId = $this->role === Role::Client->value ? $this->client_id : null;

        if ($this->isEdit) {
            Gate::authorize('update', $this->user);
            $request = new UpdateUserRequest;
            $request->merge([
                'id' => $this->user->id,
                'name' => $this->name,
                'email' => $this->email,
                'role' => $this->role,
                'client_id' => $clientId,
                'is_active' => $this->is_active,
                'password' => $this->password ?: null,
                'password_confirmation' => $this->password_confirmation ?: null,
            ]);

            $this->validate($request->rules());

            $data = [
                'name' => $this->name,
                'email' => $this->email,
                'role' => $this->role,
                'client_id' => $clientId,
                'is_active' => $this->is_active,
            ];

            if (! empty($this->password)) {
                $data['password'] = $this->password;
            }

            $this->user->update($data);
            session()->flash('status', 'User updated successfully.');
        } else {
            Gate::authorize('create', User::class);
            $request = new StoreUserRequest;
            $request->merge([
                'name' => $this->name,
                'email' => $this->email,
                'role' => $this->role,
                'client_id' => $clientId,
                'is_active' => $this->is_active,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
            ]);

            $this->validate($request->rules());

            User::create([
                'name' => $this->name,
                'email' => $this->email,
                'role' => $this->role,
                'client_id' => $clientId,
                'is_active' => $this->is_active,
                'password' => $this->password,
            ]);

            session()->flash('status', 'User created successfully.');
        }

        return redirect()->route('users.index');
    }

    /**
     * Render the component.
     *
     * @return View
     */
    public function render()
    {
        return view('livewire.users.user-form');
    }
}
