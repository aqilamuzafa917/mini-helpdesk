<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('unauthenticated users redirect to login', function () {
    $this->get('/clients')->assertRedirect(route('login'));
    $this->get('/users')->assertRedirect(route('login'));
    $this->get('/tickets')->assertRedirect(route('login'));
    $this->get('/dashboard')->assertRedirect(route('login'));
});

test('non-admin users are denied access to admin-only routes', function () {
    $engineer = User::factory()->engineer()->create();
    $client = User::factory()->client()->create();

    $adminRoutes = [
        '/clients',
        '/clients/create',
        '/users',
        '/users/create',
    ];

    foreach ($adminRoutes as $route) {
        // Engineer should get 403
        $this->actingAs($engineer)->get($route)->assertStatus(403);

        // Client user should get 403
        $this->actingAs($client)->get($route)->assertStatus(403);
    }
});
