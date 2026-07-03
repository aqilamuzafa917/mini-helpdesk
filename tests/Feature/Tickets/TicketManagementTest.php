<?php

use App\Enums\ClientStatus;
use App\Enums\Priority;
use App\Enums\TicketStatus;
use App\Livewire\Tickets\CommentThread;
use App\Livewire\Tickets\TicketForm;
use App\Models\Client;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('guests are redirected to login', function () {
    $this->get(route('tickets.index'))->assertRedirect(route('login'));
    $ticket = Ticket::factory()->create();
    $this->get(route('tickets.show', $ticket))->assertRedirect(route('login'));
});

test('property-based scope consistency check across large dynamic dataset', function () {
    // Generate dynamically 10 active clients
    $clients = Client::factory()->count(10)->create(['status' => ClientStatus::Active]);

    // Generate 15 engineers
    $engineers = User::factory()->count(15)->engineer()->create();

    // Generate 20 client users mapped to different clients
    $clientUsers = collect();
    foreach ($clients as $client) {
        $clientUsers->push(User::factory()->client()->create(['client_id' => $client->id]));
    }

    // Generate 120 tickets randomly distributed
    $tickets = collect();
    for ($i = 0; $i < 120; $i++) {
        $tickets->push(Ticket::factory()->create([
            'client_id' => $clients->random()->id,
            'assigned_engineer_id' => rand(0, 1) ? $engineers->random()->id : null,
        ]));
    }

    $queryService = new TicketQueryService;

    // 1. Admin property check (must see all 120 tickets)
    $admin = User::factory()->admin()->create();
    expect($queryService->scopedQuery($admin)->count())->toBe(120);

    // 2. Engineer property check (each engineer must see exactly their assigned tickets)
    foreach ($engineers as $engineer) {
        $expectedCount = Ticket::where('assigned_engineer_id', $engineer->id)->count();
        $actualCount = $queryService->scopedQuery($engineer)->count();
        expect($actualCount)->toBe($expectedCount);
    }

    // 3. Client User property check (each client user must see exactly their company's tickets)
    foreach ($clientUsers as $clientUser) {
        $expectedCount = Ticket::where('client_id', $clientUser->client_id)->count();
        $actualCount = $queryService->scopedQuery($clientUser)->count();
        expect($actualCount)->toBe($expectedCount);
    }
});

test('engineer is blocked from changing priority or assigned engineer on update via Livewire', function () {
    $engineer = User::factory()->engineer()->create();
    $ticket = Ticket::factory()->create([
        'assigned_engineer_id' => $engineer->id,
        'priority' => Priority::Medium,
    ]);

    // Try to update priority through the component
    Livewire::actingAs($engineer)
        ->test(TicketForm::class, ['ticket' => $ticket])
        ->set('priority', Priority::High->value)
        ->call('save')
        ->assertStatus(403);

    // Verify DB was NOT updated
    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'priority' => Priority::Medium->value,
    ]);

    // Try to update assigned engineer through the component
    $otherEngineer = User::factory()->engineer()->create();
    Livewire::actingAs($engineer)
        ->test(TicketForm::class, ['ticket' => $ticket])
        ->set('assigned_engineer_id', $otherEngineer->id)
        ->call('save')
        ->assertStatus(403);

    // Verify DB was NOT updated
    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'assigned_engineer_id' => $engineer->id,
    ]);
});

test('client is blocked from updating tickets via Livewire', function () {
    $client = Client::factory()->create();
    $clientUser = User::factory()->client()->create(['client_id' => $client->id]);
    $ticket = Ticket::factory()->create(['client_id' => $client->id]);

    // Client user trying to access the edit page gets 403 Forbidden
    $this->actingAs($clientUser)
        ->get(route('tickets.edit', $ticket))
        ->assertForbidden();
});

test('client is blocked from posting internal comments', function () {
    $client = Client::factory()->create();
    $clientUser = User::factory()->client()->create(['client_id' => $client->id]);
    $ticket = Ticket::factory()->create(['client_id' => $client->id]);

    // Client user trying to post an internal comment
    Livewire::actingAs($clientUser)
        ->test(CommentThread::class, ['ticket' => $ticket])
        ->set('comment', 'This is a secret')
        ->set('is_internal', true)
        ->call('addComment')
        ->assertStatus(403);
});

test('direct HTTP layer restrictions for Engineer (Property 12)', function () {
    $engineer = User::factory()->engineer()->create();
    $ticket = Ticket::factory()->create([
        'assigned_engineer_id' => $engineer->id,
        'priority' => Priority::Medium,
    ]);

    // Try to change priority directly via PUT endpoint
    $response = $this->actingAs($engineer)->put(route('tickets.update', $ticket), [
        'status' => TicketStatus::InProgress->value,
        'priority' => Priority::High->value,
    ]);
    $response->assertStatus(403);

    // Try to change assigned engineer directly via PUT endpoint
    $otherEngineer = User::factory()->engineer()->create();
    $response = $this->actingAs($engineer)->put(route('tickets.update', $ticket), [
        'status' => TicketStatus::InProgress->value,
        'assigned_engineer_id' => $otherEngineer->id,
    ]);
    $response->assertStatus(403);
});

test('direct HTTP layer restrictions for Client (Properties 12 and 13)', function () {
    $client = Client::factory()->create();
    $clientUser = User::factory()->client()->create(['client_id' => $client->id]);
    $ticket = Ticket::factory()->create(['client_id' => $client->id]);

    // Client is completely blocked from PUT/POST updates on ticket
    $response = $this->actingAs($clientUser)->put(route('tickets.update', $ticket), [
        'status' => TicketStatus::Resolved->value,
    ]);
    $response->assertStatus(403);
});

test('admin can CRUD tickets', function () {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->create(['status' => ClientStatus::Active]);
    $engineer = User::factory()->engineer()->create();

    // 1. Create Ticket
    Livewire::actingAs($admin)
        ->test(TicketForm::class)
        ->set('title', 'System Crash')
        ->set('description', 'Database server is down.')
        ->set('priority', 'high')
        ->set('client_id', $client->id)
        ->set('assigned_engineer_id', $engineer->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('tickets.index'));

    $this->assertDatabaseHas('tickets', [
        'title' => 'System Crash',
        'client_id' => $client->id,
        'assigned_engineer_id' => $engineer->id,
        'status' => 'open',
    ]);

    $ticket = Ticket::where('title', 'System Crash')->first();

    // 2. Read Ticket details
    $this->actingAs($admin)
        ->get(route('tickets.show', $ticket))
        ->assertSuccessful();

    // 3. Update Ticket
    Livewire::actingAs($admin)
        ->test(TicketForm::class, ['ticket' => $ticket])
        ->set('title', 'System Crash - Resolved')
        ->set('status', 'resolved')
        ->set('status_change_notes', 'Rebooted database server.')
        ->call('save')
        ->assertRedirect(route('tickets.index'));

    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'title' => 'System Crash - Resolved',
        'status' => 'resolved',
    ]);

    // Check history was logged
    $this->assertDatabaseHas('ticket_status_histories', [
        'ticket_id' => $ticket->id,
        'old_status' => 'open',
        'new_status' => 'resolved',
        'notes' => 'Rebooted database server.',
    ]);
});
