<?php

namespace App\Services;

use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class LatestDistributionService
{
    public function __construct(
        public array $latest_distributions = [],
    ) {
        //
    }

    public function getLatestDistributions(Crawler $crawler): array
    {
        for ($i = 0; $i < $crawler->filter('tr')->count(); $i++) {
            if ($i >= 1) {
                $this->latest_distributions[] = [
                    'date' => $crawler->filter('tr')->eq($i)->filter('th')->html(),
                    'distributions' => [
                        'name' => $crawler->filter('tr')->eq($i)->filter('td')->filter('a')->text(),
                        'detail' => [
                            'diwa' => route('v2.distributions.show', Str::after($crawler->filter('tr')->eq($i)->filter('td')->filter('a')->link()->getUri(), '.com/')),
                            'distrowatch' => $crawler->filter('tr')->eq($i)->filter('td')->filter('a')->link()->getUri(),
                        ],
                    ],
                    'version' => [
                        'code' => $crawler->filter('tr')->eq($i)->filter('td')->filter('a')->eq(1)->text(),
                        'download' => $crawler->filter('tr')->eq($i)->filter('td')->filter('a')->eq(1)->link()->getUri(),
                    ]
                ];
            }
        }

        return $this->latest_distributions;
    }
}
