<?php

use App\Enums\ClientStatus;
use App\Enums\Priority;
use App\Enums\TicketStatus;
use App\Models\Client;
use App\Models\MonthlyReportRemark;
use App\Models\Ticket;
use App\Models\User;
use App\Services\MonthlyReportService;
use App\Services\TicketQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->queryService = new TicketQueryService;
    $this->service = new MonthlyReportService($this->queryService);
});

test('generateReport returns accurate totals and filters by client, month, and year', function () {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->create(['status' => ClientStatus::Active]);

    // Target date: July 2026
    // Ticket 1: July 2026, status Open, priority High
    Ticket::factory()->create([
        'client_id' => $client->id,
        'status' => TicketStatus::Open,
        'priority' => Priority::High,
        'created_at' => '2026-07-15 10:00:00',
    ]);

    // Ticket 2: July 2026, status Resolved, priority Low
    Ticket::factory()->create([
        'client_id' => $client->id,
        'status' => TicketStatus::Resolved,
        'priority' => Priority::Low,
        'created_at' => '2026-07-20 14:00:00',
    ]);

    // Ticket 3: June 2026 (wrong month)
    Ticket::factory()->create([
        'client_id' => $client->id,
        'status' => TicketStatus::Open,
        'priority' => Priority::Medium,
        'created_at' => '2026-06-15 10:00:00',
    ]);

    // Ticket 4: July 2025 (wrong year)
    Ticket::factory()->create([
        'client_id' => $client->id,
        'status' => TicketStatus::Open,
        'priority' => Priority::Medium,
        'created_at' => '2025-07-15 10:00:00',
    ]);

    // Create a remark
    $remark = MonthlyReportRemark::create([
        'client_id' => $client->id,
        'month' => 7,
        'year' => 2026,
        'remarks' => 'Good month',
        'created_by' => $admin->id,
    ]);

    $report = $this->service->generateReport($admin, $client->id, 7, 2026);

    expect($report)->toBeArray()
        ->and($report['total_count'])->toBe(2)
        ->and($report['status_counts'][TicketStatus::Open->value])->toBe(1)
        ->and($report['status_counts'][TicketStatus::Resolved->value])->toBe(1)
        ->and($report['priority_counts'][Priority::High->value])->toBe(1)
        ->and($report['priority_counts'][Priority::Low->value])->toBe(1)
        ->and($report['priority_counts'][Priority::Medium->value])->toBe(0)
        ->and($report['tickets'])->toHaveCount(2)
        ->and($report['remark']->id)->toBe($remark->id);
});

test('generateReport locks client users to their own client company report only', function () {
    $client1 = Client::factory()->create(['status' => ClientStatus::Active]);
    $client2 = Client::factory()->create(['status' => ClientStatus::Active]);
    $clientUser = User::factory()->client()->create(['client_id' => $client1->id]);

    // Ticket for Client 1 in July 2026
    $ticket1 = Ticket::factory()->create([
        'client_id' => $client1->id,
        'created_at' => '2026-07-15 10:00:00',
    ]);

    // Ticket for Client 2 in July 2026
    $ticket2 = Ticket::factory()->create([
        'client_id' => $client2->id,
        'created_at' => '2026-07-15 10:00:00',
    ]);

    // Even if clientUser requests client2's ID, the service must force client1's ID
    $report = $this->service->generateReport($clientUser, $client2->id, 7, 2026);

    expect($report['client_id'])->toBe($client1->id)
        ->and($report['tickets'])->toHaveCount(1)
        ->and($report['tickets']->first()->id)->toBe($ticket1->id);
});

test('saveRemark implements upsert correctly, resulting in exactly one DB row', function () {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->create(['status' => ClientStatus::Active]);

    // Save remark first time
    $remark1 = $this->service->saveRemark($client->id, 7, 2026, 'First remark draft', $admin->id);

    $this->assertDatabaseHas('monthly_report_remarks', [
        'client_id' => $client->id,
        'month' => 7,
        'year' => 2026,
        'remarks' => 'First remark draft',
        'created_by' => $admin->id,
    ]);

    expect(MonthlyReportRemark::count())->toBe(1);

    // Save remark second time (updates the existing one)
    $remark2 = $this->service->saveRemark($client->id, 7, 2026, 'Final SLA remarks', $admin->id);

    $this->assertDatabaseHas('monthly_report_remarks', [
        'id' => $remark1->id,
        'client_id' => $client->id,
        'month' => 7,
        'year' => 2026,
        'remarks' => 'Final SLA remarks',
        'created_by' => $admin->id,
    ]);

    expect(MonthlyReportRemark::count())->toBe(1); // Still exactly one row in the DB!
});
