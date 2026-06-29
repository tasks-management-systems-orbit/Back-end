<?php

use App\Providers\AppServiceProvider;
use App\Providers\FirebaseServiceProvider;
use JeroenNoten\LaravelAdminLte\AdminLteServiceProvider;

return [
    AppServiceProvider::class,
    AdminLteServiceProvider::class,
    FirebaseServiceProvider::class,
];
