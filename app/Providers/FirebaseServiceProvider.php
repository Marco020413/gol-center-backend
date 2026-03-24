<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;

class FirebaseServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('firebase', function ($app) {
            $credentialsPath = base_path(env('FIREBASE_CREDENTIALS'));
            return (new Factory)
                ->withServiceAccount($credentialsPath)
                ->withDatabaseUri(env('FIREBASE_DATABASE_URL'));
        });

        $this->app->singleton('firebase.database', function ($app) {
            return $app->make('firebase')->createDatabase();
        });

        $this->app->singleton('firebase.auth', function ($app) {
            return $app->make('firebase')->createAuth();
        });
    }

    public function boot()
    {
        //
    }
}