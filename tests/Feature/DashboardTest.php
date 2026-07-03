<?php

use App\Enums\ClientStatus;
use App\Enums\TicketStatus;
use App\Livewire\Dashboard\AdminDashboard;
use App\Livewire\Dashboard\ClientDashboard;
use App\Livewire\Dashboard\EngineerDashboard;
use App\Models\Client;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('admin redirects to admin dashboard and loads metrics with accurate values', function () {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->create(['status' => ClientStatus::Active]);
    $engineer = User::factory()->engineer()->create();

    // Create tickets: 2 Open (1 assigned, 1 unassigned), 1 In Progress (assigned), 1 Resolved (assigned), 1 Closed (unassigned)
    Ticket::factory()->create(['client_id' => $client->id, 'status' => TicketStatus::Open, 'assigned_engineer_id' => $engineer->id]);
    Ticket::factory()->create(['client_id' => $client->id, 'status' => TicketStatus::Open, 'assigned_engineer_id' => null]);
    Ticket::factory()->create(['client_id' => $client->id, 'status' => TicketStatus::InProgress, 'assigned_engineer_id' => $engineer->id]);
    Ticket::factory()->create(['client_id' => $client->id, 'status' => TicketStatus::Resolved, 'assigned_engineer_id' => $engineer->id]);
    Ticket::factory()->create(['client_id' => $client->id, 'status' => TicketStatus::Closed, 'assigned_engineer_id' => null]);

    $this->actingAs($admin);

    // Verify /dashboard dispatcher redirects to /admin/dashboard
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('admin.dashboard'));

    // Check dashboard rendering and parameters
    Livewire::test(AdminDashboard::class)
        ->assertViewHas('metrics', function ($metrics) {
            return $metrics['total_count'] === 5 &&
                $metrics['unassigned_count'] === 2 &&
                $metrics['open_count'] === 2 &&
                $metrics['status_counts'][TicketStatus::Open->value] === 2 &&
                $metrics['status_counts'][TicketStatus::InProgress->value] === 1 &&
                $metrics['status_counts'][TicketStatus::Resolved->value] === 1 &&
                $metrics['status_counts'][TicketStatus::Closed->value] === 1;
        })
        ->assertViewHas('recentTickets');
});

test('engineer redirects to engineer dashboard and loads metrics with accurate values', function () {
    $engineer = User::factory()->engineer()->create();
    $otherEngineer = User::factory()->engineer()->create();
    $client = Client::factory()->create(['status' => ClientStatus::Active]);

    // Create tickets for engineer: 1 Open, 2 In Progress
    Ticket::factory()->create(['client_id' => $client->id, 'status' => TicketStatus::Open, 'assigned_engineer_id' => $engineer->id]);
    Ticket::factory()->count(2)->create(['client_id' => $client->id, 'status' => TicketStatus::InProgress, 'assigned_engineer_id' => $engineer->id]);

    // Create tickets for other engineer/unassigned
    Ticket::factory()->create(['client_id' => $client->id, 'status' => TicketStatus::Open, 'assigned_engineer_id' => $otherEngineer->id]);
    Ticket::factory()->create(['client_id' => $client->id, 'status' => TicketStatus::Open, 'assigned_engineer_id' => null]);

    $this->actingAs($engineer);

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('engineer.dashboard'));

    Livewire::test(EngineerDashboard::class)
        ->assertViewHas('metrics', function ($metrics) {
            return $metrics['total_count'] === 3 &&
                $metrics['status_counts'][TicketStatus::Open->value] === 1 &&
                $metrics['status_counts'][TicketStatus::InProgress->value] === 2 &&
                $metrics['status_counts'][TicketStatus::Resolved->value] === 0 &&
                $metrics['status_counts'][TicketStatus::Closed->value] === 0;
        })
        ->assertViewHas('recentTickets');
});

test('client redirects to client dashboard and loads metrics with accurate values', function () {
    $client = Client::factory()->create(['status' => ClientStatus::Active]);
    $otherClient = Client::factory()->create(['status' => ClientStatus::Active]);
    $clientUser = User::factory()->client()->create(['client_id' => $client->id]);

    // Create tickets for client: 2 Open, 1 Resolved
    Ticket::factory()->count(2)->create(['client_id' => $client->id, 'status' => TicketStatus::Open]);
    Ticket::factory()->create(['client_id' => $client->id, 'status' => TicketStatus::Resolved]);

    // Create tickets for other client
    Ticket::factory()->create(['client_id' => $otherClient->id, 'status' => TicketStatus::Open]);

    $this->actingAs($clientUser);

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('client.dashboard'));

    Livewire::test(ClientDashboard::class)
        ->assertViewHas('metrics', function ($metrics) {
            return $metrics['total_count'] === 3 &&
                $metrics['status_counts'][TicketStatus::Open->value] === 2 &&
                $metrics['status_counts'][TicketStatus::InProgress->value] === 0 &&
                $metrics['status_counts'][TicketStatus::Resolved->value] === 1 &&
                $metrics['status_counts'][TicketStatus::Closed->value] === 0;
        })
        ->assertViewHas('recentTickets');
});

test('dashboard displays at most 5 recent tickets', function () {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->create(['status' => ClientStatus::Active]);

    // Create 7 tickets
    Ticket::factory()->count(7)->create(['client_id' => $client->id]);

    Livewire::actingAs($admin)
        ->test(AdminDashboard::class)
        ->assertViewHas('recentTickets', function ($tickets) {
            return $tickets->count() === 5;
        });
});
