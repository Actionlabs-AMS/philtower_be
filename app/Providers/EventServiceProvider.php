<?php

namespace App\Providers;

use App\Events\TicketAssigned;
use App\Events\TicketClosed;
use App\Events\TicketCreated;
use App\Events\TicketStatusChanged;
use App\Listeners\SendCsatSurvey;
use App\Listeners\SendTicketAssignedNotification;
use App\Listeners\SendTicketCreatedNotification;
use App\Listeners\SendTicketStatusNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        TicketCreated::class => [
            SendTicketCreatedNotification::class,
        ],
        TicketStatusChanged::class => [
            SendTicketStatusNotification::class,
        ],
        TicketAssigned::class => [
            SendTicketAssignedNotification::class,
        ],
        TicketClosed::class => [
            SendCsatSurvey::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
