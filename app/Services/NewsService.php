<?php

namespace App\Services;

use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class NewsService
{
    public function __construct(
        public array $news = [],
        public array $body = [],
        public array $relatedNewsAndReleases = [],
        public array $distributionSummaries = [],
        public string $about = '',
        public string $screenshots = '',
        public string $headline = '',
        public string $thumbnail = '',
        public string $date = '',
        public string|null $distrowatchNewsUrl = null,
        public string|null $distributionUrl = null,
        public string|null $newsDetailUrl = null,
        public string|null $distrowatchDistributionUrl = null,
        public bool $sponsor = false,
    ) {
    }

    public function getNews(Crawler $crawler): array
    {
        $crawler->reduce(function ($node, $i) {
            if ($i >= 1) {
                $headline = $node->children()->filter('td')->nextAll()->text();

                if (Str::contains($headline, 'DistroWatch Weekly')) {
                    // Weekly news
                    $newsDetailParams = $node->children()->filter('td')->nextAll()->filter('a')->nextAll()->attr('href');

                    $this->newsDetailUrl = route("weekly.show", Str::after($newsDetailParams, 'weekly.php?issue='));

                    $this->distrowatchNewsUrl = $node->children()->filter('td')->nextAll()->filter('a')->nextAll()->link()->getUri();

                    $this->distributionUrl = null;
                    $this->distrowatchDistributionUrl =  null;
                } elseif (Str::contains($headline, 'Featured Distribution')) {
                    // sponsor news
                    $href = Str::after($node->children()->filter('.NewsLogo')->filter('a')->attr('href'), '?distribution=');

                    $this->distributionUrl = route("v2.distributions.show", $href);

                    $this->distrowatchDistributionUrl = $node->children()->filter('.NewsLogo')->filter('a')->eq(0)->link()->getUri();

                    $this->sponsor = true;

                    $this->distrowatchNewsUrl = null;
                    $this->newsDetailUrl = null;
                } else {
                    // distribution news
                    $newsDetailParams = $node->children()->filter('td')->nextAll()->filter('a')->nextAll()->attr('href');

                    $href = Str::after($node->children()->filter('.NewsLogo')->filter('a')->attr('href'), '?distribution=');

                    $this->newsDetailUrl = route("v2.news.show", $newsDetailParams);

                    $this->distributionUrl = route("v2.distributions.show", $href);

                    $this->distrowatchDistributionUrl = $node->children()->filter('.NewsLogo')->filter('a')->eq(0)->link()->getUri();

                    $this->distrowatchNewsUrl = $node->children()->filter('td')->nextAll()->filter('a')->nextAll()->link()->getUri();
                }

                $this->news[] = [
                    'headline' => Str::remove('NEW • ', $headline),
                    'date' => $node->children()->filter('td')->text(),
                    'thumbnail' => config('app.distrowatch_url') . $node->children()->filter('.NewsLogo')->filter('a')->eq(0)->children('img')->attr('src'),
                    'body' => $node->children()->filter('.NewsText')->text(),
                    'detail' => [
                        'news' => [
                            'distrowatch' => $this->distrowatchNewsUrl,
                            'diwa' =>  $this->newsDetailUrl,
                        ],
                        'distribution' => [
                            'distrowatch' => $this->distrowatchDistributionUrl,
                            'diwa' => $this->distributionUrl,
                        ]
                    ],
                    'sponsor' => $this->sponsor
                ];
            }
        });

        return $this->news;
    }

    public function getDistributionSummary(Crawler $summary): array
    {
        $this->distributionSummaries['distribution'] = $summary->eq(2)->text();

        $this->distributionSummaries['home_page'] = $summary->eq(4)->text();

        $this->distributionSummaries['mailing_lists'] = $summary->eq(6)->text() != '--' ? $summary->eq(6)->text() : null;

        $this->distributionSummaries['user_forum'] = $summary->eq(8)->text() != '--' ? $summary->eq(8)->text() : null;

        $summary->eq(12)->filter('a')->each(function ($node) {
            $this->distributionSummaries['documentation'][] = $node->link()->getUri();
        });

        $summary->eq(14)->filter('a')->each(function ($node) {
            $this->distributionSummaries['gallery'][] = $node->link()->getUri();
        });

        $summary->eq(16)->filter('a')->each(function ($node) {
            if (count($node) > 0) {
                $this->distributionSummaries['screencasts'][] = $node->link()->getUri();
            } else {
                $this->distributionSummaries['screencasts'] = null;
            }
        });

        $summary->eq(18)->filter('a')->each(function ($node) {
            if (count($node) > 0) {
                $this->distributionSummaries['download_mirrors'][] = $node->link()->getUri();
            } else {
                $this->distributionSummaries['download_mirrors'] = null;
            }
        });

        $this->distributionSummaries['bug_tracker'] = $summary->eq(20)->filter('a')->link()->getUri();

        $summary->eq(22)->filter('a')->each(function ($node) {
            $this->distributionSummaries['related_websites'][] = $node->link()->getUri();
        });

        $summary->eq(24)->filter('a')->each(function ($node) {
            $this->distributionSummaries['reviews'][] = $node->link()->getUri();
        });

        if (count($summary->eq(26)->filter('a')) > 0) {
            $this->distributionSummaries['where_to_buy']['text'] = $summary->eq(26)->filter('a')->text();
            $this->distributionSummaries['where_to_buy']['url'] = $summary->eq(26)->filter('a')->link()->getUri();
        } else {
            $this->distributionSummaries['where_to_buy'] = null;
        }

        return $this->distributionSummaries;
    }

    public function getScreenshot(Crawler $filter_info_class_element): string
    {
        $filter_info_class_element->eq(31)->each(function ($node) {
            $this->screenshots = config('app.distrowatch_url') . $node->filter('img')->attr('src');
        });

        return $this->screenshots;
    }

    public function getRecentRelatedNews(Crawler $crawler): array
    {
        $crawler->eq(0)->each(function ($node) {
            $node->filter('a')->each(function ($item, $i) {
                $this->relatedNewsAndReleases[] = [
                    'text' => $item->text(),
                    'url' => $item->link()->getUri()
                ];
            });
        });

        return $this->relatedNewsAndReleases;
    }

    public function getDistributionDetailUrl(): string
    {
        $this->distributionUrl = route("v2.distributions.show", Str::remove(config('app.distrowatch_url'), $this->distrowatchDistributionUrl));

        return $this->distributionUrl;
    }

    public function getAboutText(Crawler $crawler): string
    {
        $crawler->eq(1)->each(function ($node) {
            $this->about = $node->text();
        });

        return $this->about;
    }

    public function getDistrowatchNewsUrl(Crawler $crawler): string
    {
        $crawler->each(function ($node) {
            $this->distrowatchNewsUrl = $node->children()->filter('td')->nextAll()->filter('a')->eq(1)->link()->getUri();
        });

        return $this->distrowatchNewsUrl;
    }

    public function getNewsDate(Crawler $crawler): string
    {
        $crawler->each(function ($node) {
            $this->date = $node->children()->filter('td')->text();
        });

        return $this->date;
    }

    public function getNewsHeadline(Crawler $crawler): string
    {
        $crawler->each(function ($node) {
            $this->headline = Str::remove('NEW • ', $node->children()->filter('td')->nextAll()->text());
        });

        return $this->headline;
    }

    public function getNewsThumbnail(Crawler $crawler): string
    {
        $crawler->each(function ($node) {
            $this->thumbnail = config('app.distrowatch_url') . $node->children()->filter('.NewsLogo')->filter('a')->eq(0)->children('img')->attr('src');
        });

        return $this->thumbnail;
    }

    public function getDistrowatchDistributionUrl(Crawler $crawler): string
    {
        $crawler->each(function ($node) {
            $this->distrowatchDistributionUrl = $node->children()->filter('.NewsLogo')->filter('a')->eq(0)->link()->getUri();
        });

        return $this->distrowatchDistributionUrl;
    }

    public function getNewsBody(Crawler $crawler): array
    {
        $crawler->each(function ($node) {
            $this->body = [
                'text' => $node->children()->filter('.NewsText')->text(),
                'html' => $node->children()->filter('.NewsText')->html()
            ];
        });

        return $this->body;
    }
}
