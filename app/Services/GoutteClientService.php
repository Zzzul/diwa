<?php

namespace App\Services;

use Goutte\Client;
use Symfony\Component\HttpClient\HttpClient;

class GoutteClientService
{
    public function setup(): Client
    {
        return new Client(HttpClient::create(['verify_peer' => false, 'verify_host' => false]));
    }
}
