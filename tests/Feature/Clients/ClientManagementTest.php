<?php

use App\Enums\ClientStatus;
use App\Livewire\Clients\ClientForm;
use App\Livewire\Clients\ClientTable;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('guest is redirected to login', function () {
    $this->get(route('clients.index'))->assertRedirect(route('login'));
    $this->get(route('clients.create'))->assertRedirect(route('login'));
});

test('non-admin roles are forbidden from accessing clients pages', function () {
    $engineer = User::factory()->engineer()->create();
    $clientUser = User::factory()->client()->create();

    $this->actingAs($engineer)->get(route('clients.index'))->assertForbidden();
    $this->actingAs($clientUser)->get(route('clients.index'))->assertForbidden();

    $this->actingAs($engineer)->get(route('clients.create'))->assertForbidden();
    $this->actingAs($clientUser)->get(route('clients.create'))->assertForbidden();
});

test('admin can see clients list', function () {
    $admin = User::factory()->admin()->create();
    Client::factory()->create(['name' => 'Acme Test Corp']);

    $this->actingAs($admin)
        ->get(route('clients.index'))
        ->assertSuccessful();

    Livewire::actingAs($admin)
        ->test(ClientTable::class)
        ->assertSee('Acme Test Corp');
});

test('admin can search and filter clients', function () {
    $admin = User::factory()->admin()->create();
    Client::factory()->create(['name' => 'Alpha Corporation', 'status' => ClientStatus::Active]);
    Client::factory()->create(['name' => 'Beta Industries', 'status' => ClientStatus::Inactive]);

    // Search filter
    Livewire::actingAs($admin)
        ->test(ClientTable::class)
        ->set('search', 'Alpha')
        ->assertSee('Alpha Corporation')
        ->assertDontSee('Beta Industries');

    // Status Filter
    Livewire::actingAs($admin)
        ->test(ClientTable::class)
        ->set('statusFilter', 'inactive')
        ->assertSee('Beta Industries')
        ->assertDontSee('Alpha Corporation');
});

test('admin can toggle client status from the list', function () {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->create(['status' => ClientStatus::Active]);

    Livewire::actingAs($admin)
        ->test(ClientTable::class)
        ->call('toggleStatus', $client)
        ->assertHasNoErrors();

    expect($client->refresh()->status)->toBe(ClientStatus::Inactive);
});

test('admin can access create client page and submit valid data', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('clients.create'))
        ->assertSuccessful();

    Livewire::actingAs($admin)
        ->test(ClientForm::class)
        ->set('name', 'New Client')
        ->set('contact_person', 'Jane Doe')
        ->set('email', 'jane@newclient.com')
        ->set('phone', '123456789')
        ->set('status', 'active')
        ->call('save')
        ->assertRedirect(route('clients.index'));

    $this->assertDatabaseHas('clients', [
        'name' => 'New Client',
        'email' => 'jane@newclient.com',
    ]);
});

test('client creation requires valid input and unique email', function () {
    $admin = User::factory()->admin()->create();
    Client::factory()->create(['email' => 'existing@client.com']);

    // Required fields validation
    Livewire::actingAs($admin)
        ->test(ClientForm::class)
        ->call('save')
        ->assertHasErrors(['name', 'contact_person', 'email', 'phone']);

    // Unique email validation
    Livewire::actingAs($admin)
        ->test(ClientForm::class)
        ->set('name', 'Test')
        ->set('contact_person', 'Test')
        ->set('phone', '123')
        ->set('email', 'existing@client.com')
        ->call('save')
        ->assertHasErrors(['email' => 'unique']);
});

test('admin can update client and reuse existing email', function () {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->create([
        'name' => 'Original Name',
        'email' => 'keep@client.com',
    ]);

    $this->actingAs($admin)
        ->get(route('clients.edit', $client))
        ->assertSuccessful();

    // Verify loading of existing data and successful update keeping same email
    Livewire::actingAs($admin)
        ->test(ClientForm::class, ['client' => $client])
        ->assertSet('name', 'Original Name')
        ->assertSet('email', 'keep@client.com')
        ->set('name', 'Updated Name')
        ->call('save')
        ->assertRedirect(route('clients.index'));

    $this->assertDatabaseHas('clients', [
        'id' => $client->id,
        'name' => 'Updated Name',
        'email' => 'keep@client.com',
    ]);
});
