<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Policies\ClientPolicy;
use App\Policies\TicketCommentPolicy;
use App\Policies\TicketPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Client::class => ClientPolicy::class,
        User::class => UserPolicy::class,
        Ticket::class => TicketPolicy::class,
        TicketComment::class => TicketCommentPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
