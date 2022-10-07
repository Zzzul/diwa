<?php

namespace App\Services;

use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class LatestHeadlineService
{
    public function __construct(public array $headlines = [])
    {
        //
    }

    public function get(Crawler $crawler)
    {
        for ($i = 0; $i < $crawler->filter('tr')->count(); $i++) {
            if ($i >= 1) {
                $filter = $crawler->filter('tr')->eq($i)->filter('td')->filter('a');

                $this->packages[] = [
                    'title' => $filter->text(),
                    'url' => $filter->link()->getUri(),
                ];
            }
        }

        $this->packages[] = [
            'title' => 'See more',
            'url' => 'https://distrowatch.com/dwres.php?resource=headlines&newstype=news'
        ];

        return $this->packages;
    }
}
