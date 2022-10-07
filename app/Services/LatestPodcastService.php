<?php

namespace App\Services;

use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class LatestPodcastService
{
    public function __construct(public array $podcasts = [])
    {
        //
    }

    public function get(Crawler $crawler): array
    {
        for ($i = 0; $i < $crawler->filter('tr')->count(); $i++) {
            if ($i >= 1) {
                $filter = $crawler->filter('tr')->eq($i)->filter('td')->filter('a');

                $this->podcasts[] = [
                    'name' => $filter->text(),
                    'released_date' => $crawler->filter('tr')->eq($i)->filter('th')->html(),
                    'website' => $filter->link()->getUri(),
                    'source' => $filter->eq(1)->link()->getUri(),
                    'download' => $filter->eq(2)->link()->getUri(),
                ];
            }
        }

        return $this->podcasts;
    }
}
