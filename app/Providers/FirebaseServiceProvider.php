<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;

class FirebaseServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('firebase', function ($app) {
            $credentials = env('FIREBASE_JSON');

            $factory = (new Factory);

            if ($credentials && str_starts_with(trim($credentials), '{')) {
                $jsonConfig = json_decode(trim($credentials), true);
                $factory = $factory->withServiceAccount($jsonConfig);
            } else {
                $path = env('FIREBASE_CREDENTIALS', 'storage/app/firebase-auth.json');
                $factory = $factory->withServiceAccount(base_path($path));
            }

            return $factory->withDatabaseUri(env('FIREBASE_DATABASE_URL'));
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