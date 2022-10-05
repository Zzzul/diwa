<?php

namespace App\Services;

use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class LatestPackageService
{
    public function __construct(public array $packages = [])
    {
        //
    }

    public function get(Crawler $crawler): array
    {
        for ($i = 0; $i < $crawler->filter('tr')->count(); $i++) {
            if ($i >= 1) {
                $filter = $crawler->filter('tr')->eq($i)->filter('td')->filter('a');

                $this->packages[] = [
                    'name' => $filter->eq(1)->text(),
                    'released_date' => $crawler->filter('tr')->eq($i)->filter('th')->html(),
                    'website' => $filter->eq(1)->link()->getUri(),
                    'version' => $filter->eq(2)->text(),
                    'download' => $filter->eq(2)->link()->getUri(),
                ];
            }
        }

        return $this->packages;
    }
}
