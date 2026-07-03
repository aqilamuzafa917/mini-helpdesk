<?php

use App\Enums\ClientStatus;
use App\Enums\Role;
use App\Livewire\Users\UserForm;
use App\Livewire\Users\UserTable;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('guest is redirected to login', function () {
    $this->get(route('users.index'))->assertRedirect(route('login'));
    $this->get(route('users.create'))->assertRedirect(route('login'));
});

test('non-admin roles are forbidden from accessing users pages', function () {
    $engineer = User::factory()->engineer()->create();
    $clientUser = User::factory()->client()->create();

    $this->actingAs($engineer)->get(route('users.index'))->assertForbidden();
    $this->actingAs($clientUser)->get(route('users.index'))->assertForbidden();

    $this->actingAs($engineer)->get(route('users.create'))->assertForbidden();
    $this->actingAs($clientUser)->get(route('users.create'))->assertForbidden();
});

test('admin can see users list', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->engineer()->create(['name' => 'John Engineer']);

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertSuccessful();

    Livewire::actingAs($admin)
        ->test(UserTable::class)
        ->assertSee('John Engineer');
});

test('admin can search and filter users', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->engineer()->create(['name' => 'Alice Dev']);
    User::factory()->client()->create(['name' => 'Bob Client']);

    // Search filter
    Livewire::actingAs($admin)
        ->test(UserTable::class)
        ->set('search', 'Alice')
        ->assertSee('Alice Dev')
        ->assertDontSee('Bob Client');

    // Role filter
    Livewire::actingAs($admin)
        ->test(UserTable::class)
        ->set('roleFilter', 'client')
        ->assertSee('Bob Client')
        ->assertDontSee('Alice Dev');
});

test('admin can toggle user status unless it is their own account', function () {
    $admin = User::factory()->admin()->create();
    $engineer = User::factory()->engineer()->create(['is_active' => true]);

    // Deactivate engineer
    Livewire::actingAs($admin)
        ->test(UserTable::class)
        ->call('toggleStatus', $engineer)
        ->assertHasNoErrors();

    expect($engineer->refresh()->is_active)->toBeFalse();

    // Try to self-deactivate admin
    Livewire::actingAs($admin)
        ->test(UserTable::class)
        ->call('toggleStatus', $admin);

    // Should stay active
    expect($admin->refresh()->is_active)->toBeTrue();
});

test('admin can access create user page and submit valid data', function () {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->create(['status' => ClientStatus::Active]);

    $this->actingAs($admin)
        ->get(route('users.create'))
        ->assertSuccessful();

    // Create client user
    Livewire::actingAs($admin)
        ->test(UserForm::class)
        ->set('name', 'Bob Client')
        ->set('email', 'bob@example.com')
        ->set('role', 'client')
        ->set('client_id', $client->id)
        ->set('is_active', true)
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('save')
        ->assertRedirect(route('users.index'));

    $this->assertDatabaseHas('users', [
        'name' => 'Bob Client',
        'email' => 'bob@example.com',
        'role' => 'client',
        'client_id' => $client->id,
    ]);

    // Check password is correctly hashed
    $user = User::where('email', 'bob@example.com')->first();
    expect(Hash::check('password123', $user->password))->toBeTrue();
});

test('user creation validation for role conditions', function () {
    $admin = User::factory()->admin()->create();

    // Client role requires client_id
    Livewire::actingAs($admin)
        ->test(UserForm::class)
        ->set('name', 'Bob')
        ->set('email', 'bob@example.com')
        ->set('role', 'client')
        ->set('client_id', null)
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('save')
        ->assertHasErrors(['client_id' => 'required']);

    // Admin role prohibits client_id
    Livewire::actingAs($admin)
        ->test(UserForm::class)
        ->set('name', 'Bob')
        ->set('email', 'bob@example.com')
        ->set('role', 'admin')
        ->set('client_id', 99)
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('save')
        ->assertHasErrors(['client_id' => 'prohibited']);
});

test('admin can update user and reuse email without changing password', function () {
    $admin = User::factory()->admin()->create();
    $engineer = User::factory()->engineer()->create([
        'name' => 'Alice Original',
        'email' => 'alice@example.com',
        'password' => 'originalhash',
    ]);
    $originalPassword = $engineer->password;

    $this->actingAs($admin)
        ->get(route('users.edit', $engineer))
        ->assertSuccessful();

    // Verify loading data and updating without password change
    Livewire::actingAs($admin)
        ->test(UserForm::class, ['user' => $engineer])
        ->assertSet('name', 'Alice Original')
        ->assertSet('email', 'alice@example.com')
        ->set('name', 'Alice Updated')
        ->set('password', '')
        ->set('password_confirmation', '')
        ->call('save')
        ->assertRedirect(route('users.index'));

    $this->assertDatabaseHas('users', [
        'id' => $engineer->id,
        'name' => 'Alice Updated',
        'email' => 'alice@example.com',
    ]);

    // Password must remain unchanged
    expect($engineer->refresh()->password)->toBe($originalPassword);
});
