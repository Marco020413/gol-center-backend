<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;

class FirebaseConnProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('firebase', function ($app) {
            $credentials = env('FIREBASE_CREDENTIALS');
            $factory = (new Factory);

            // Validamos si la variable es un JSON (Railway) o una ruta (Local)
            if (str_starts_with($credentials, '{')) {
                // Es un JSON directo, lo decodificamos
                $factory = $factory->withServiceAccount(json_decode($credentials, true));
            } else {
                // Es una ruta de archivo, usamos base_path
                $factory = $factory->withServiceAccount(base_path($credentials));
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