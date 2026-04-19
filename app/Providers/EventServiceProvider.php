<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \App\Events\TaskCompleted::class => [
            \App\Listeners\UpdateUserStats::class,
        ],
        \App\Events\ProjectCreated::class => [
            \App\Listeners\UpdateUserProjectsCount::class,
        ],
        \App\Events\UserJoinedProject::class => [
            \App\Listeners\UpdateUserProjectsCountOnJoin::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}
