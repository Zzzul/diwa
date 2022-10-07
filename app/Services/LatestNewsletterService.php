<?php

namespace App\Services;

use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class LatestNewsletterService
{
    public function __construct(public array $newsletters = [])
    {
        //
    }

    public function get(Crawler $crawler): array
    {
        for ($i = 0; $i < $crawler->filter('tr')->count(); $i++) {
            if ($i >= 1) {
                $filter = $crawler->filter('tr')->eq($i)->filter('td')->filter('a');

                $this->newsletters[] = [
                    'title' => $filter->text(),
                    'released_date' => $crawler->filter('tr')->eq($i)->filter('th')->html(),
                    'url' => $filter->link()->getUri(),
                ];
            }
        }

        return $this->newsletters;
    }
}
