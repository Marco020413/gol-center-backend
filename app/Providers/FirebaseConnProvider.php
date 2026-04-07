<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;

class FirebaseConnProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('firebase', function ($app) {
            $factory = (new Factory);

            $jsonConfig = [
                "type" => "service_account",
                "project_id" => "gol-center",
                "private_key_id" => "de792390db9dae2d2e5470277c7ff7cb97eb1a33",
                "private_key" => "-----BEGIN PRIVATE KEY-----\n" .
                    "MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQDKBxgUBeLc6qEt\n" .
                    "POMunU+/Tb5ib0dTM82IzS+mYK1Z28SoGZq5D7Dx3F8aLVzVrAg9SstRSeRKfna7\n" .
                    "MtRBPna5+EzaEL+ggMkt7zfTQ1hmpvPm1s56Jyzqpq2NcOjpFPv6jZPdyq5JmDfI\n" .
                    "WrNqWc+EfWPGngrdLKg4itWqifluzsAhOEGW4krdyZvtrMALykqumXe/Ovse0exx\n" .
                    "HoBXYRpsMekRBoig6LOIWkZlKnvqJ/fpjRmQ2euIXx43QogUr1WlUW/a2glOHnI4\n" .
                    "l+2RvbiAxvyh8thTrWWcBKTRCb7prf3JXIrA4u421N9mvVu8WAH/UdEQHQ/ebTd2\n" .
                    "0dpRxd1DAgMBAAECggEAXuSsI6OMJtYydKw3zYpojeP3fAbmyqb3cL1oF08rvZxj\n" .
                    "p823BhvfsgsfxIzwIREYIaoDDCCYEYGLRWyflDHB1KJTfs4FJF+5n1DQuPPWDwl2\n" .
                    "YMQe4fOKvoEh3eDeFbUcklhzzubHrJvJ/9rSkS60MXgwKHfNhIvYTA6yQ9NhDPFM\n" .
                    "WldHY1W2ynVpvrZ24lShMCNPE4ddTRYPVVU+qjwYxU1EJuBOpFLCxJl9X2K3U/tq\n" .
                    "3pKZNLxJdPc3pEKKameBt+ZwxS9BU/Y9f7JAKuWfNwE4Wk4f/fFgkCOoLHT6k6gG\n" .
                    "PsdJlX+VU9AjO9jJImGU6jOxAHDmqU65ux0tCU1tgQKBgQDktxpevDZtAJWljuZv\n" .
                    "P72MQXfe1d0GWaV9QGqhkRDg3GxhQuyo5H4UMmO+FrC1oJh/agTgmmC0Rn5W1qfH\n" .
                    "JA6JRdC0EFHRaTNaJwCT8fAn5NXGWq33kJrjR69swkOQpgEomtvC1uveWA4wt3kR\n" .
                    "AP+jUOm9bIr+ZNhsL+wfOaYpQQKBgQDiIPYlGLff00WKktU0gb1T0EP1WS8cqN7K\n" .
                    "Qi/bjWHScduOeTrJFeVz/QStvTkLA/0SeWHraSR/DtgABh0SjE0aL2Iwy8VhW3yz\n" .
                    "LRhg126s2rW7JKk6YK3pQ5cjtxPlZE+/3EApEQRWEqyTwbQrxNhBT1rRoaze6yUl\n" .
                    "zy1Q7LWBgwKBgBVR0B52kug0Pr7RBD/ohCW30JGwA8tcveVgBNCMsjMTuPKUB3Vy\n" .
                    "3yHI1K1adhxoHO77lfrNySfkzlAP8FHK1aQMIvk18DqCAOxtaEtiKQ2+8gJsX30N\n" .
                    "OLLo8rxDm4K3RrJfRQ7zc8clFCWMZLemDS5PE4q5vHccNWRYTz1V5VxBAoGAQ3ZR\n" .
                    "+P9G3B/Lv2vO77tFIJwhqjDRPVukG/NjE706Ue5l8jt4rkU6D0CNBMhPHFo0Ri6w\n" .
                    "Z+sWWFG1X0xzn/T7i7RXNveUC1ef/FSX0Ux0WF1nmk11r1TojY78qEsWRHmI0VSE\nT7wj7wnjMrG3btdrLRDfp2r0iAzfKEiG3H3CKMECgYBL6TUVRDklmf3KCVGrYXBX\n+FBdZYSqZjVBOIgA4PM2xdO4VAxqaTH5xygNKyZ6GI2w9DvU7L5GkJU4jCxP6a37\n0TDXJmH0CPlmnsXFs135/EsSG6yCMeP0YFpdQOsjjD4/k6A5APMmw34Yefylxuzm\nnrROwijVnTQz3oFXRy99fg==\n" .
                    "-----END PRIVATE KEY-----\n",
                "client_email" => "firebase-adminsdk-fbsvc@gol-center.iam.gserviceaccount.com",
                "client_id" => "108706629305050832514",
                "auth_uri" => "https://accounts.google.com/o/oauth2/auth",
                "token_uri" => "https://oauth2.googleapis.com/token",
                "auth_provider_x509_cert_url" => "https://www.googleapis.com/oauth2/v1/certs",
                "client_x509_cert_url" => "https://www.googleapis.com/robot/v1/metadata/x509/firebase-adminsdk-fbsvc%40gol-center.iam.gserviceaccount.com",
                "universe_domain" => "googleapis.com"
            ];

            return $factory
                ->withServiceAccount($jsonConfig)
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