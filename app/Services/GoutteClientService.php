<?php

namespace App\Services;

use Goutte\Client;
use Symfony\Component\HttpClient\HttpClient;

class GoutteClientService
{
    public function setup(): Client
    {
        if (env('APP_ENV') == 'local') {
            return new Client(HttpClient::create(['verify_peer' => false, 'verify_host' => false]));
        }

        return new Client();
    }
}
