<?php

namespace Modules\Itenant\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

//Handlers
use Modules\Isite\Events\Handlers\SetMaintenanceMode;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
    ];

    public function register(): void
    {
        Event::listen(
            "Modules\\Itenant\\Events\\OrganizationWasUpdated",
            [SetMaintenanceMode::class, 'handle']
        );

    }

}
