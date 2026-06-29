<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;

class FirebaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('firebase.messaging', function ($app) {
            $factory = new Factory();
            $credentialsPath = config('firebase.credentials');

            return $factory
                ->withServiceAccount($credentialsPath)
                ->createMessaging();
        });
    }

    public function provides(): array
    {
        return ['firebase.messaging'];
    }
}
