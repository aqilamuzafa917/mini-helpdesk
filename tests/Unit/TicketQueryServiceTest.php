<?php

use App\Enums\ClientStatus;
use App\Enums\Priority;
use App\Enums\TicketStatus;
use App\Models\Client;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->service = new TicketQueryService;
});

test('scopedQuery scopes tickets correctly by user role', function () {
    // Set up clients
    $clientA = Client::factory()->create(['status' => ClientStatus::Active]);
    $clientB = Client::factory()->create(['status' => ClientStatus::Active]);

    // Set up users
    $admin = User::factory()->admin()->create();
    $engineer1 = User::factory()->engineer()->create();
    $engineer2 = User::factory()->engineer()->create();
    $clientUserA = User::factory()->client()->create(['client_id' => $clientA->id]);
    $clientUserB = User::factory()->client()->create(['client_id' => $clientB->id]);

    // Set up tickets
    // Client A, assigned to Engineer 1
    $ticket1 = Ticket::factory()->create([
        'client_id' => $clientA->id,
        'assigned_engineer_id' => $engineer1->id,
    ]);

    // Client B, assigned to Engineer 1
    $ticket2 = Ticket::factory()->create([
        'client_id' => $clientB->id,
        'assigned_engineer_id' => $engineer1->id,
    ]);

    // Client A, unassigned
    $ticket3 = Ticket::factory()->create([
        'client_id' => $clientA->id,
        'assigned_engineer_id' => null,
    ]);

    // Client B, assigned to Engineer 2
    $ticket4 = Ticket::factory()->create([
        'client_id' => $clientB->id,
        'assigned_engineer_id' => $engineer2->id,
    ]);

    // 1. Admin must see all tickets
    $adminTickets = $this->service->scopedQuery($admin)->get();
    expect($adminTickets)->toHaveCount(4)
        ->and($adminTickets->pluck('id'))->toContain($ticket1->id, $ticket2->id, $ticket3->id, $ticket4->id);

    // 2. Engineer 1 must see only their assigned tickets
    $eng1Tickets = $this->service->scopedQuery($engineer1)->get();
    expect($eng1Tickets)->toHaveCount(2)
        ->and($eng1Tickets->pluck('id'))->toContain($ticket1->id, $ticket2->id)
        ->and($eng1Tickets->pluck('id'))->not->toContain($ticket3->id, $ticket4->id);

    // 3. Client User A must see only their company's tickets
    $clientATickets = $this->service->scopedQuery($clientUserA)->get();
    expect($clientATickets)->toHaveCount(2)
        ->and($clientATickets->pluck('id'))->toContain($ticket1->id, $ticket3->id)
        ->and($clientATickets->pluck('id'))->not->toContain($ticket2->id, $ticket4->id);
});

test('filteredQuery applies filters correctly', function () {
    $admin = User::factory()->admin()->create();
    $clientA = Client::factory()->create(['status' => ClientStatus::Active]);
    $clientB = Client::factory()->create(['status' => ClientStatus::Active]);

    $ticket1 = Ticket::factory()->create([
        'client_id' => $clientA->id,
        'status' => TicketStatus::Open,
        'priority' => Priority::High,
        'ticket_number' => 'TKT-00001',
        'title' => 'Database issue',
        'description' => 'Cannot connect to MySQL server',
    ]);

    $ticket2 = Ticket::factory()->create([
        'client_id' => $clientB->id,
        'status' => TicketStatus::InProgress,
        'priority' => Priority::Low,
        'ticket_number' => 'TKT-00002',
        'title' => 'Slow frontend page load',
        'description' => 'The CSS loads slowly',
    ]);

    // Filter by status
    $statusFiltered = $this->service->filteredQuery($admin, ['status' => TicketStatus::InProgress->value])->get();
    expect($statusFiltered)->toHaveCount(1)
        ->and($statusFiltered->first()->id)->toBe($ticket2->id);

    // Filter by priority
    $priorityFiltered = $this->service->filteredQuery($admin, ['priority' => Priority::High->value])->get();
    expect($priorityFiltered)->toHaveCount(1)
        ->and($priorityFiltered->first()->id)->toBe($ticket1->id);

    // Filter by client_id
    $clientFiltered = $this->service->filteredQuery($admin, ['client_id' => $clientB->id])->get();
    expect($clientFiltered)->toHaveCount(1)
        ->and($clientFiltered->first()->id)->toBe($ticket2->id);

    // Filter by search matching ticket number
    $searchTicketNum = $this->service->filteredQuery($admin, ['search' => 'TKT-00001'])->get();
    expect($searchTicketNum)->toHaveCount(1)
        ->and($searchTicketNum->first()->id)->toBe($ticket1->id);

    // Filter by search matching title
    $searchTitle = $this->service->filteredQuery($admin, ['search' => 'Slow frontend'])->get();
    expect($searchTitle)->toHaveCount(1)
        ->and($searchTitle->first()->id)->toBe($ticket2->id);

    // Filter by search matching description
    $searchDesc = $this->service->filteredQuery($admin, ['search' => 'MySQL'])->get();
    expect($searchDesc)->toHaveCount(1)
        ->and($searchDesc->first()->id)->toBe($ticket1->id);
});
