<?php

use App\Enums\Role;
use App\Livewire\Clients\ClientForm;
use App\Livewire\Clients\ClientTable;
use App\Livewire\Dashboard\AdminDashboard;
use App\Livewire\Dashboard\ClientDashboard;
use App\Livewire\Dashboard\EngineerDashboard;
use App\Livewire\Reports\MonthlyReport;
use App\Livewire\Tickets\TicketDetail;
use App\Livewire\Tickets\TicketForm;
use App\Livewire\Tickets\TicketTable;
use App\Livewire\Users\UserForm;
use App\Livewire\Users\UserTable;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// Dashboard role dispatcher
Route::middleware(['auth'])->get('/dashboard', function () {
    return match (auth()->user()->role) {
        Role::Admin => redirect()->route('admin.dashboard'),
        Role::Engineer => redirect()->route('engineer.dashboard'),
        Role::Client => redirect()->route('client.dashboard'),
    };
})->name('dashboard');

// Admin-only routes
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/clients', ClientTable::class)->name('clients.index');
    Route::get('/clients/create', ClientForm::class)->name('clients.create');
    Route::get('/clients/{client}/edit', ClientForm::class)->name('clients.edit');

    Route::get('/users', UserTable::class)->name('users.index');
    Route::get('/users/create', UserForm::class)->name('users.create');
    Route::get('/users/{user}/edit', UserForm::class)->name('users.edit');
});

// Shared routes (access controlled at component/policy levels)
Route::middleware(['auth'])->group(function () {
    // Dashboards
    Route::get('/admin/dashboard', AdminDashboard::class)->middleware('role:admin')->name('admin.dashboard');
    Route::get('/engineer/dashboard', EngineerDashboard::class)->middleware('role:engineer')->name('engineer.dashboard');
    Route::get('/client/dashboard', ClientDashboard::class)->middleware('role:client')->name('client.dashboard');

    // Tickets
    Route::get('/tickets', TicketTable::class)->name('tickets.index');
    Route::get('/tickets/create', TicketForm::class)->name('tickets.create');
    Route::get('/tickets/{ticket}', TicketDetail::class)->name('tickets.show');
    Route::get('/tickets/{ticket}/edit', TicketForm::class)->name('tickets.edit');

    // Reports
    Route::get('/reports/monthly', MonthlyReport::class)->name('reports.monthly');
    Route::get('/reports/monthly/print', function () {
        return view('reports.monthly-print');
    })->name('reports.monthly.print');
});

// Disable registration view by overriding/handling the /register route
Route::get('/register', function () {
    return redirect('/login');
})->name('register');

require __DIR__.'/settings.php';
