<?php

use App\Enums\Priority;
use App\Enums\TicketStatus;
use App\Models\Client;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('every ticket creation produces a unique, correctly formatted ticket number', function () {
    $client = Client::factory()->create();
    $user = User::factory()->create();

    // Create 10 tickets and assert formatting and incrementing
    for ($i = 1; $i <= 10; $i++) {
        $ticket = Ticket::create([
            'client_id' => $client->id,
            'created_by' => $user->id,
            'title' => 'Test Ticket '.$i,
            'description' => 'Description '.$i,
            'priority' => Priority::Low,
            'status' => TicketStatus::Open,
        ]);

        $expectedNumber = sprintf('TKT-%05d', $i);
        expect($ticket->ticket_number)->toBe($expectedNumber);
    }
});

test('every status change produces a TicketStatusHistory row with correct values', function () {
    $client = Client::factory()->create();
    $user = User::factory()->create();

    $ticket = Ticket::create([
        'client_id' => $client->id,
        'created_by' => $user->id,
        'title' => 'Status History Test',
        'description' => 'Description',
        'priority' => Priority::Medium,
        'status' => TicketStatus::Open,
    ]);

    // Initial status history check (null -> open)
    $this->assertDatabaseHas('ticket_status_histories', [
        'ticket_id' => $ticket->id,
        'old_status' => null,
        'new_status' => 'open',
    ]);

    // Change status to in_progress
    $this->actingAs($user);
    $ticket->status_change_notes = 'Starting work';
    $ticket->update(['status' => TicketStatus::InProgress]);

    $this->assertDatabaseHas('ticket_status_histories', [
        'ticket_id' => $ticket->id,
        'old_status' => 'open',
        'new_status' => 'in_progress',
        'notes' => 'Starting work',
        'changed_by' => $user->id,
    ]);
});

test('resolved_at is set on transition to Resolved and cleared on transition away', function () {
    $client = Client::factory()->create();
    $user = User::factory()->create();

    $ticket = Ticket::create([
        'client_id' => $client->id,
        'created_by' => $user->id,
        'title' => 'Resolution test',
        'description' => 'Description',
        'priority' => Priority::High,
        'status' => TicketStatus::Open,
    ]);

    expect($ticket->resolved_at)->toBeNull();

    // Transition to Resolved
    $ticket->update(['status' => TicketStatus::Resolved]);
    expect($ticket->fresh()->resolved_at)->not->toBeNull();

    // Transition away from Resolved to Closed
    $ticket->update(['status' => TicketStatus::Closed]);
    expect($ticket->fresh()->resolved_at)->toBeNull();
});
