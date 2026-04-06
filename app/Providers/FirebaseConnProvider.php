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
                "private_key_id" => "9a7517d787704756f28fbbb2555fc66ef71f7636",
                "private_key" => "-----BEGIN PRIVATE KEY-----\nMIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQC9LCQNikZZPqQH\nqReK6hwS9hNCnSpHR/udsvLnBgfLkic8Wh8UZxrb5v+IvxkzgJi/todp09LN+IQT\nS7vRccaoV/iHakgYAX5SSdjhPBTTVEMsMQBwF15fDxPK7IdnHPaRAIak+hSgC/U8\nxpryg7dsJcq7K1DM2275vI/Pyhk0ttXPomm4uRAiVpx6H8m1SGT2v85AF1vK7ljy\n7/pnZ8fnZ+HKgz1oInevA5wHxjYteRvaV97C6dSCyGMO6iZmefcGugdCr9iYR5pU\n8mWK1FXtBCdisDM/f7/xhp0G7Ifz558nuySx2jQIV/49PcBTIv8hoPEDlbAgT37q\n+4pm0WrnAgMBAAECggEAIAdRM1mt/34zl3hxm/t3NV6kdCrX9amiWFjr92F2AGmG\naxl3GaU7TvAVY7m3MpkbQ5pL4wcSMf1Zn0q7SgLRd78tPQAppKbcFGKcbcYWYJFr\nrhe90zyhpcaPu1PAuRyWQoasRVJyG4bqxpd5tIXIkG71nCsBSy375Byg3LWC/wGL\nPIYwuShRzEGxKeprlzyRj29OnBPt5HP9ENC0NqVRLXOTqXy1FtrHzvO20G6q8mcx\nc3yz42l8nJ0qFeqPqQxSzkwgY7bNbjSexciKAv9LubjYhUMuUyUgw6qWen/TCKyc\nm0FhdQ2jLo9COa6blyimQRZjE/EpkQVGcjo2SwOywQKBgQDdrrMCrf8G7uf2pN4h\n9JWW3URD5+beTw9FGGrru+9F3ajGV+bnkh386x6nayCuKKzf+jCLRkU4YpwWGaDO\nwakOmS+06dNIYaH4C1CnYBRtJidotDhpOxwzsDVnKfBGW9HfgLq50Icq5Ncfg/Mj\nxyOBJDbjGeVUxoexw9CusOw16wKBgQDadREO1P4TD+oAMIlTjNSQLQbvx8vaf7Q4\n1276MoqZmWrqyed9EUSOeW5T8H/8RhYobw3eppnneXbltj2V8g80OeKTsF3r1Ght\nE2JBUL25UY3hm+zosc5TBRHaYhmo8T2twITUrO1U5onlWL3YxL+BdOjCL1vhe8St\nDJLxvQwz9QKBgA17nKOjFYnEahNUP7Zs+9QrLQW8SwxmXpVjQM2JpqnnK2a03fRj\nrLM/UaP5Hgm5q5oLy3wNEBS32qp1cJRirveWVZJ5TkJuvb0qYwNrEpL46RizLWMp\nFzQFmZnmsRh2aTNgzvqSNkZjmLnv+o/Igt5N55C0eIBJeuNS95RIanV1AoGBAMyG\n+fd29oGGTsDqsEem/IQXg/0OhA8HrQXivSZGF3mDJ5S2f6KafiMeEWtJKapZO/mT\nL9sbA9Cx6d1baAFw5UcyQq2fW++lqDcnBMC+BULx1fI59+5MOi2D+5kIa5m+cs6w\nBizP0kvAo640DVpbqihb+meDy3QqWpLhyNnG8xvZAoGBAKzJyCSZTCHKTw1s/LnM\nks+uyogH12gMcgShd3G6gBD6/ryEwT0n6r0xl2vy7rTidPi3WRDDkOvsavh7q+tm\nsgCIfk5jvmFs+ol9YS1YxVCGIjqfbL9DLtCSpe6c1DV6/ZzIMQ6CPRuVystjoMJG\nRsfz5pSKN1og09Qq52Nzff8l\n-----END PRIVATE KEY-----\n",
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