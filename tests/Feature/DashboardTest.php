<?php

use App\Enums\ClientStatus;
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

test('admin redirects to admin dashboard and loads metrics', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    // Verify /dashboard dispatcher redirects to /admin/dashboard
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('admin.dashboard'));

    // Check dashboard rendering and parameters
    Livewire::test(AdminDashboard::class)
        ->assertViewHas('metrics', function ($metrics) {
            return array_key_exists('status_counts', $metrics) &&
                array_key_exists('unassigned_count', $metrics) &&
                array_key_exists('open_count', $metrics) &&
                array_key_exists('total_count', $metrics);
        })
        ->assertViewHas('recentTickets');
});

test('engineer redirects to engineer dashboard and loads metrics', function () {
    $engineer = User::factory()->engineer()->create();

    $this->actingAs($engineer);

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('engineer.dashboard'));

    Livewire::test(EngineerDashboard::class)
        ->assertViewHas('metrics', function ($metrics) {
            return array_key_exists('status_counts', $metrics) &&
                array_key_exists('total_count', $metrics);
        })
        ->assertViewHas('recentTickets');
});

test('client redirects to client dashboard and loads metrics', function () {
    $client = Client::factory()->create(['status' => ClientStatus::Active]);
    $clientUser = User::factory()->client()->create(['client_id' => $client->id]);

    $this->actingAs($clientUser);

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('client.dashboard'));

    Livewire::test(ClientDashboard::class)
        ->assertViewHas('metrics', function ($metrics) {
            return array_key_exists('status_counts', $metrics) &&
                array_key_exists('total_count', $metrics);
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
