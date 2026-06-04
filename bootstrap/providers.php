<?php

use App\Providers\AppServiceProvider;
use App\Providers\BroadcastServiceProvider;
use JeroenNoten\LaravelAdminLte\AdminLteServiceProvider;

return [
    AppServiceProvider::class,
    AdminLteServiceProvider::class,
    BroadcastServiceProvider::class,
];
