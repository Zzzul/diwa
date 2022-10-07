<?php

namespace App\Services;

use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class LatestReviewService
{
    public function __construct(public array $reviews = [])
    {
        //
    }

    public function get(Crawler $crawler): array
    {
        for ($i = 0; $i < $crawler->filter('tr')->count(); $i++) {
            if ($i >= 1) {
                $filter = $crawler->filter('tr')->eq($i)->filter('td')->filter('a');

                $removeDistrowatchUrl = Str::after($filter->link()->getUri(), '?issue=');

                $this->reviews[] = [
                    'title' => $filter->text(),
                    'released_date' => $crawler->filter('tr')->eq($i)->filter('th')->html(),
                    'url' => $filter->link()->getUri(),
                    'weekly' => route('v2.weekly.show', Str::before($removeDistrowatchUrl, '#'))
                ];
            }
        }

        return $this->reviews;
    }
}
