<?php

namespace App\Services;

use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class RandomDistributionService
{
    public function get(Crawler $crawler): array
    {
        $distributionUrl = $crawler->filter('a')->link()->getUri();
        $distributionName = $crawler->filter('img')->attr('title');

        return [
            'name' => $distributionName,
            // remove 2 duplicate name and then append name again
            'description' => $distributionName . Str::remove($distributionName . ' ', $crawler->text()),
            'status' => Str::remove('Status: ', $crawler->filter('b')->text()),
            'detail' => [
                'diwa' => route('v2.distributions.show', Str::after($distributionUrl, '.com/')),
                'distrowatch' => $distributionUrl,
            ]
        ];
    }
}
