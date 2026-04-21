<?php

namespace app\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \app\Events\TaskCompleted::class => [
            \app\Listeners\UpdateUserStats::class,
        ],
        \app\Events\ProjectCreated::class => [
            \app\Listeners\UpdateUserProjectsCount::class,
        ],
        \app\Events\UserJoinedProject::class => [
            \app\Listeners\UpdateUserProjectsCountOnJoin::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}
