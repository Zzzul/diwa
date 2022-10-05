<?php

namespace App\Services;

use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class LatestDistributionService
{
    public function __construct(public array $latest_distributions = [])
    {
        //
    }

    public function get(Crawler $crawler): array
    {
        for ($i = 0; $i < $crawler->filter('tr')->count(); $i++) {
            if ($i >= 1) {

                $filter = $crawler->filter('tr')->eq($i)->filter('td')->filter('a');

                $this->latest_distributions[] = [
                    'name' => $filter->text(),
                    'released_date' => $crawler->filter('tr')->eq($i)->filter('th')->html(),
                    'version' => $filter->eq(1)->text(),
                    'download' => $filter->eq(1)->link()->getUri(),
                    'detail' => [
                        'diwa' => route('v2.distributions.show', Str::after($filter->link()->getUri(), '.com/')),
                        'distrowatch' => $filter->link()->getUri(),
                    ],
                ];
            }
        }

        return $this->latest_distributions;
    }
}
