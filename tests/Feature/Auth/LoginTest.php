<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('inactive user login is denied with generic error', function () {
    $user = User::factory()->inactive()->create([
        'email' => 'inactive@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->post(route('login.store'), [
        'email' => 'inactive@example.com',
        'password' => 'password123',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('authenticated user redirects to correct dashboard based on role', function () {
    $admin = User::factory()->admin()->create();
    $engineer = User::factory()->engineer()->create();
    $client = User::factory()->client()->create();

    // 1. Admin dashboard redirect
    $response = $this->actingAs($admin)->get('/dashboard');
    $response->assertRedirect('/admin/dashboard');

    // 2. Engineer dashboard redirect
    $response = $this->actingAs($engineer)->get('/dashboard');
    $response->assertRedirect('/engineer/dashboard');

    // 3. Client dashboard redirect
    $response = $this->actingAs($client)->get('/dashboard');
    $response->assertRedirect('/client/dashboard');
});

test('already authenticated user is redirected to dashboard when visiting login', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('login'));

    $response->assertRedirect('/dashboard');
});
