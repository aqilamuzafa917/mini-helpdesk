<?php

namespace App\Livewire\Clients;

use App\Enums\ClientStatus;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

class ClientForm extends Component
{
    /**
     * The Client model instance being created or edited.
     */
    public ?Client $client = null;

    /**
     * Indicates if the component is in edit mode.
     */
    public bool $isEdit = false;

    /**
     * Bound input properties.
     */
    public string $name = '';

    public string $contact_person = '';

    public string $email = '';

    public string $phone = '';

    public string $status = 'active';

    /**
     * Mount the component and initialize bindings.
     */
    public function mount(?Client $client = null): void
    {
        if ($client && $client->exists) {
            Gate::authorize('update', $client);
            $this->client = $client;
            $this->isEdit = true;

            $this->name = $client->name;
            $this->contact_person = $client->contact_person;
            $this->email = $client->email;
            $this->phone = $client->phone;
            $this->status = $client->status->value;
        } else {
            Gate::authorize('create', Client::class);
            $this->status = ClientStatus::Active->value;
        }
    }

    /**
     * Save the client record (create or update).
     *
     * @return RedirectResponse
     */
    public function save()
    {
        if ($this->isEdit) {
            Gate::authorize('update', $this->client);
            $request = new UpdateClientRequest;
            $request->merge([
                'id' => $this->client->id,
                'name' => $this->name,
                'contact_person' => $this->contact_person,
                'email' => $this->email,
                'phone' => $this->phone,
                'status' => $this->status,
            ]);
            $this->validate($request->rules());

            $this->client->update([
                'name' => $this->name,
                'contact_person' => $this->contact_person,
                'email' => $this->email,
                'phone' => $this->phone,
                'status' => $this->status,
            ]);

            session()->flash('status', 'Client updated successfully.');
        } else {
            Gate::authorize('create', Client::class);
            $request = new StoreClientRequest;
            $request->merge([
                'name' => $this->name,
                'contact_person' => $this->contact_person,
                'email' => $this->email,
                'phone' => $this->phone,
                'status' => $this->status,
            ]);
            $this->validate($request->rules());

            Client::create([
                'name' => $this->name,
                'contact_person' => $this->contact_person,
                'email' => $this->email,
                'phone' => $this->phone,
                'status' => $this->status,
            ]);

            session()->flash('status', 'Client created successfully.');
        }

        return redirect()->route('clients.index');
    }

    /**
     * Render the component view.
     *
     * @return View
     */
    public function render()
    {
        return view('livewire.clients.client-form');
    }
}
