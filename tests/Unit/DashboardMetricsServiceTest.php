<?php

use App\Enums\ClientStatus;
use App\Enums\TicketStatus;
use App\Models\Client;
use App\Models\Ticket;
use App\Models\User;
use App\Services\DashboardMetricsService;
use App\Services\TicketQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->queryService = new TicketQueryService;
    $this->service = new DashboardMetricsService($this->queryService);
});

test('getAdminMetrics returns accurate dashboard aggregate counts', function () {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->create(['status' => ClientStatus::Active]);
    $engineer = User::factory()->engineer()->create();

    // Create 2 open tickets (1 assigned, 1 unassigned)
    Ticket::factory()->create([
        'client_id' => $client->id,
        'status' => TicketStatus::Open,
        'assigned_engineer_id' => $engineer->id,
    ]);
    Ticket::factory()->create([
        'client_id' => $client->id,
        'status' => TicketStatus::Open,
        'assigned_engineer_id' => null,
    ]);

    // Create 3 in progress tickets (2 assigned, 1 unassigned)
    Ticket::factory()->create([
        'client_id' => $client->id,
        'status' => TicketStatus::InProgress,
        'assigned_engineer_id' => $engineer->id,
    ]);
    Ticket::factory()->create([
        'client_id' => $client->id,
        'status' => TicketStatus::InProgress,
        'assigned_engineer_id' => $engineer->id,
    ]);
    Ticket::factory()->create([
        'client_id' => $client->id,
        'status' => TicketStatus::InProgress,
        'assigned_engineer_id' => null,
    ]);

    // Create 1 resolved ticket
    Ticket::factory()->create([
        'client_id' => $client->id,
        'status' => TicketStatus::Resolved,
        'assigned_engineer_id' => $engineer->id,
    ]);

    $metrics = $this->service->getAdminMetrics($admin);

    expect($metrics)->toBeArray()
        ->and($metrics['total_count'])->toBe(6)
        ->and($metrics['unassigned_count'])->toBe(2) // 2 unassigned
        ->and($metrics['open_count'])->toBe(2)
        ->and($metrics['status_counts'][TicketStatus::Open->value])->toBe(2)
        ->and($metrics['status_counts'][TicketStatus::InProgress->value])->toBe(3)
        ->and($metrics['status_counts'][TicketStatus::Resolved->value])->toBe(1)
        ->and($metrics['status_counts'][TicketStatus::Closed->value])->toBe(0);
});

test('getEngineerMetrics returns counts scoped to that engineer only', function () {
    $engineer1 = User::factory()->engineer()->create();
    $engineer2 = User::factory()->engineer()->create();
    $client = Client::factory()->create(['status' => ClientStatus::Active]);

    // Engineer 1 tickets: 2 Open, 1 Resolved
    Ticket::factory()->count(2)->create([
        'client_id' => $client->id,
        'status' => TicketStatus::Open,
        'assigned_engineer_id' => $engineer1->id,
    ]);
    Ticket::factory()->create([
        'client_id' => $client->id,
        'status' => TicketStatus::Resolved,
        'assigned_engineer_id' => $engineer1->id,
    ]);

    // Engineer 2 tickets: 1 InProgress
    Ticket::factory()->create([
        'client_id' => $client->id,
        'status' => TicketStatus::InProgress,
        'assigned_engineer_id' => $engineer2->id,
    ]);

    // Unassigned tickets: 1 Open
    Ticket::factory()->create([
        'client_id' => $client->id,
        'status' => TicketStatus::Open,
        'assigned_engineer_id' => null,
    ]);

    $metrics = $this->service->getEngineerMetrics($engineer1);

    expect($metrics)->toBeArray()
        ->and($metrics['total_count'])->toBe(3)
        ->and($metrics['status_counts'][TicketStatus::Open->value])->toBe(2)
        ->and($metrics['status_counts'][TicketStatus::InProgress->value])->toBe(0)
        ->and($metrics['status_counts'][TicketStatus::Resolved->value])->toBe(1)
        ->and($metrics['status_counts'][TicketStatus::Closed->value])->toBe(0);
});

test('getClientMetrics returns counts scoped to that client company only', function () {
    $clientA = Client::factory()->create(['status' => ClientStatus::Active]);
    $clientB = Client::factory()->create(['status' => ClientStatus::Active]);
    $clientUserA = User::factory()->client()->create(['client_id' => $clientA->id]);

    // Client A tickets: 1 Open, 2 InProgress
    Ticket::factory()->create([
        'client_id' => $clientA->id,
        'status' => TicketStatus::Open,
    ]);
    Ticket::factory()->count(2)->create([
        'client_id' => $clientA->id,
        'status' => TicketStatus::InProgress,
    ]);

    // Client B tickets: 2 Open
    Ticket::factory()->count(2)->create([
        'client_id' => $clientB->id,
        'status' => TicketStatus::Open,
    ]);

    $metrics = $this->service->getClientMetrics($clientUserA);

    expect($metrics)->toBeArray()
        ->and($metrics['total_count'])->toBe(3)
        ->and($metrics['status_counts'][TicketStatus::Open->value])->toBe(1)
        ->and($metrics['status_counts'][TicketStatus::InProgress->value])->toBe(2)
        ->and($metrics['status_counts'][TicketStatus::Resolved->value])->toBe(0);
});

test('getRecentTickets retrieves latest tickets up to the specified limit', function () {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->create(['status' => ClientStatus::Active]);

    // Create 7 tickets with sequential updated_at times
    $tickets = collect();
    for ($i = 1; $i <= 7; $i++) {
        $tickets->push(
            Ticket::factory()->create([
                'client_id' => $client->id,
                'updated_at' => now()->addMinutes($i),
            ])
        );
    }

    $recent = $this->service->getRecentTickets($admin, 5);

    expect($recent)->toHaveCount(5)
        // Order must be updated_at DESC (latest first)
        ->and($recent->first()->id)->toBe($tickets->last()->id)
        ->and($recent->last()->id)->toBe($tickets->get(2)->id); // Index 2 is the 3rd created ticket, which is the 5th latest
});
