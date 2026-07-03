<?php

use App\Enums\ClientStatus;
use App\Enums\Priority;
use App\Enums\TicketStatus;
use App\Livewire\Reports\MonthlyReport;
use App\Models\Client;
use App\Models\MonthlyReportRemark;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('guests are redirected to login', function () {
    $this->get(route('reports.monthly'))->assertRedirect(route('login'));
    $this->get(route('reports.monthly.print'))->assertRedirect(route('login'));
});

test('role-based access to monthly report page', function () {
    $admin = User::factory()->admin()->create();
    $engineer = User::factory()->engineer()->create();
    $client = Client::factory()->create(['status' => ClientStatus::Active]);
    $clientUser = User::factory()->client()->create(['client_id' => $client->id]);

    // Admin can access
    $this->actingAs($admin)->get(route('reports.monthly'))->assertOk();

    // Client can access
    $this->actingAs($clientUser)->get(route('reports.monthly'))->assertOk();

    // Engineer is blocked (403)
    $this->actingAs($engineer)->get(route('reports.monthly'))->assertForbidden();
});

test('client is restricted to their own company report', function () {
    $client1 = Client::factory()->create(['status' => ClientStatus::Active]);
    $client2 = Client::factory()->create(['status' => ClientStatus::Active]);

    $clientUser = User::factory()->client()->create(['client_id' => $client1->id]);

    // 1. Livewire mounting forces client_id to client's own company
    Livewire::actingAs($clientUser)
        ->test(MonthlyReport::class, ['client_id' => $client2->id])
        ->assertSet('client_id', $client1->id);

    // 2. Direct HTTP print route access to another client is blocked (403)
    $this->actingAs($clientUser)
        ->get(route('reports.monthly.print', [
            'client_id' => $client2->id,
            'month' => 7,
            'year' => 2026,
        ]))
        ->assertForbidden();
});

test('admin can update remarks but client cannot', function () {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->create(['status' => ClientStatus::Active]);
    $clientUser = User::factory()->client()->create(['client_id' => $client->id]);

    // 1. Admin saves remarks successfully
    Livewire::actingAs($admin)
        ->test(MonthlyReport::class)
        ->set('client_id', $client->id)
        ->set('month', 7)
        ->set('year', 2026)
        ->set('remarks', 'Outstanding SLA performance')
        ->call('saveRemarks')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('monthly_report_remarks', [
        'client_id' => $client->id,
        'month' => 7,
        'year' => 2026,
        'remarks' => 'Outstanding SLA performance',
    ]);

    // 2. Client is blocked from saving remarks
    Livewire::actingAs($clientUser)
        ->test(MonthlyReport::class)
        ->set('remarks', 'Client hacking remarks')
        ->call('saveRemarks')
        ->assertStatus(403);
});

test('monthly report component computes accurate metrics', function () {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->create(['status' => ClientStatus::Active]);

    // Target: July 2026
    // Create 2 tickets for July 2026
    Ticket::factory()->create([
        'client_id' => $client->id,
        'status' => TicketStatus::Open,
        'priority' => Priority::High,
        'created_at' => '2026-07-05 10:00:00',
    ]);
    Ticket::factory()->create([
        'client_id' => $client->id,
        'status' => TicketStatus::Resolved,
        'priority' => Priority::Low,
        'created_at' => '2026-07-10 12:00:00',
    ]);

    // Create 1 ticket for a different month (June 2026)
    Ticket::factory()->create([
        'client_id' => $client->id,
        'created_at' => '2026-06-15 10:00:00',
    ]);

    Livewire::actingAs($admin)
        ->test(MonthlyReport::class)
        ->set('client_id', $client->id)
        ->set('month', 7)
        ->set('year', 2026)
        ->assertViewHas('report', function ($report) {
            return $report['total_count'] === 2 &&
                $report['status_counts'][TicketStatus::Open->value] === 1 &&
                $report['status_counts'][TicketStatus::Resolved->value] === 1 &&
                $report['priority_counts'][Priority::High->value] === 1 &&
                $report['priority_counts'][Priority::Low->value] === 1 &&
                $report['tickets']->count() === 2;
        });
});

test('admin saving remarks twice results in a single database record update (upsert)', function () {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->create(['status' => ClientStatus::Active]);

    // Save first remark
    Livewire::actingAs($admin)
        ->test(MonthlyReport::class)
        ->set('client_id', $client->id)
        ->set('month', 7)
        ->set('year', 2026)
        ->set('remarks', 'First remark draft')
        ->call('saveRemarks')
        ->assertHasNoErrors();

    expect(MonthlyReportRemark::count())->toBe(1);

    // Save second remark (updates the existing one)
    Livewire::actingAs($admin)
        ->test(MonthlyReport::class)
        ->set('client_id', $client->id)
        ->set('month', 7)
        ->set('year', 2026)
        ->set('remarks', 'Final SLA remarks')
        ->call('saveRemarks')
        ->assertHasNoErrors();

    expect(MonthlyReportRemark::count())->toBe(1);
    $this->assertDatabaseHas('monthly_report_remarks', [
        'client_id' => $client->id,
        'month' => 7,
        'year' => 2026,
        'remarks' => 'Final SLA remarks',
    ]);
});
