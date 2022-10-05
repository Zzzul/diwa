<?php

namespace App\Services;

use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class WeeklyNewsService
{
    public function __construct(
        public array $news = [],
        public array $content = [],
    ) {
        //
    }

    public function getAllWeeklyNews(Crawler $crawler): array
    {
        $crawler->filter('.List')->each(function ($node) {
            $url = $node->filter('a')->link()->getUri();

            $this->news[] = [
                'title' => Str::remove('• ', $node->text()),
                'detail' => [
                    'distrowatch' => $url,
                    'diwa' => route("v2.weekly.show", Str::after($url, '?issue=')),
                ]
            ];
        });

        return $this->news;
    }

    public function getWeeklyNewsTitle(Crawler $crawler): string
    {
        return $crawler->filter('.rTitle')->text();
    }

    public function getWeeklyNewsStory(Crawler $crawler): string
    {
        $removeUlText = Str::remove($crawler->filter('.rStory')->filter('ul')->text(), $crawler->filter('.rStory')->text());

        return Str::before($removeUlText, ' Content: ');
    }

    public function getWeeklyNewsContent(Crawler $crawler): array
    {
        $crawler->filter('.rStory')->eq(0)->filter('ul')->filter('a')->each(function ($node) {
            $this->content[] = [
                'url' =>  $node->link()->getUri(),
                'text' => $node->text()
            ];
        });

        return  $this->content;
    }
}
