<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\TicketObserverServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    TicketObserverServiceProvider::class,
];
