<?php

namespace App\Services;

use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class DistributionService
{
    /**
     * @var distributions
     */
    protected array $distributions;

    /**
     * @var basedOns
     */
    protected mixed $basedOns;

    /**
     * @var architectures
     */
    protected array $architectures;

    /**
     * @var desktop
     */
    protected array $desktops;

    /**
     * @var categories
     */
    protected array $categories;

    /**
     * @var mirrorLinks
     */
    protected array $mirrorLinks;

    /**
     * @var documentations
     */
    protected array $documentations;

    /**
     * @var screenhots
     */
    protected array $screenhots;

    /**
     * @var screencasts
     */
    protected mixed $screencasts;

    /**
     * @var reviews
     */
    protected array $reviews;

    /**
     * @var newsAndRealeses
     */
    protected mixed $newsAndRealeses;

    /**
     * @var relatedWebsites
     */
    protected mixed $relatedWebsites;

    /**
     * Get all Distribution.
     *
     * @param Symfony\Component\DomCrawler\Crawler $node
     * @param string $baseUrl
     * @return array
     */
    public function getAllDistribution(Crawler $node, string $baseUrl): array
    {
        $node->each(function ($node, $i) use ($baseUrl) {
            if ($i > 0) {
                $this->distributions[] = [
                    'slug' => $node->attr('value'),
                    'name' => $node->text(),
                    'detail' => [
                        'distrowatch' => $baseUrl . $node->attr('value'),
                        'diwa' => route("distributions.show", $node->attr('value')),
                    ]
                ];
            }
        });

        return (array) $this->distributions;
    }

    public function getDistributionName(Crawler $node): string
    {
        return $node->text();
    }

    public function getOsType(Crawler $node): string
    {
        return $node->eq(1)->filter('li')->eq(0)->filter('a')->text();
    }

    public function getLastUpdate(Crawler $node): string
    {
        return Str::remove('Last Update: ', $node->nextAll()->text());
    }

    public function getAboutText(Crawler $node): string
    {
        $removeUlText = Str::remove($node->filter('.TablesTitle')->filter('ul')->text(), $node->filter('.TablesTitle')->text());

        $removePopularityText = Str::before($removeUlText, ' Popularity (hits per day)');

        return Str::after($removePopularityText, ' UTC  ');
    }

    public function getBasedOns(Crawler $node): mixed
    {
        if(count($node->eq(1)->filter('li')->eq(1)->filter('a')) != 0){
            $this->basedOns = [];

            $node->eq(1)->filter('li')->eq(1)->filter('a')->each(function ($node) {
                $this->basedOns[] = Str::remove('Based on: ', $node->text());
            });
        }else{
            $this->basedOns = null;
        }

        return (array) $this->basedOns;
    }

    public function getOrigin(Crawler $node): string
    {
        return $node->eq(1)->filter('li')->eq(2)->filter('a')->text();
    }

    public function getArchitectures(Crawler $node): array
    {
        $node->eq(1)->filter('li')->eq(3)->filter('a')->each(function ($node) {
            $this->architectures[] = Str::remove('Architecture: ', $node->text());
        });

        return (array) $this->architectures;
    }

    public function getDesktopTypes(Crawler $node): array
    {
        $node->eq(1)->filter('li')->eq(4)->filter('a')->each(function ($node) {
            $this->desktop[] = Str::remove('Desktop: ', $node->text());
        });

        return (array) $this->desktop;
    }

    public function getCategories(Crawler $node): array
    {
        $node->eq(1)->filter('li')->eq(5)->filter('a')->each(function ($node) {
            $this->categories[] = Str::remove('Category: ', $node->text());
        });

        return (array) $this->categories;
    }

    public function getStatus(Crawler $node): string
    {
        return Str::remove('Status: ', $node->eq(1)->filter('li')->eq(6)->text());
    }

    public function getPopularity(Crawler $node): string
    {
        return Str::remove('Popularity: ', $node->eq(1)->filter('li')->eq(7)->text());
    }

    public function getHomepageUrl(Crawler $node): string
    {
        return $node->eq(1)->filter('a')->link()->getUri();
    }

    public function getMailingList(Crawler $node): string|null
    {
        $filterText = Str::remove('Mailing Lists ', $node->eq(2)->text());

        return Str::contains($filterText, '--') ? $this->mailingList = null : $this->mailingList = $filterText;
    }

    public function getUserForumUrl(Crawler $node): string|null
    {
        return count($node->eq(3)->filter('a')) != 0 ? $node->eq(3)->filter('a')->link()->getUri() : null;
    }

    public function getAlternativeUserForum(Crawler $node): string
    {
        return $node->eq(4)->text();
    }

    public function getDocumentation(Crawler $node): array
    {
        $node->eq(5)->filter('a')->each(function ($node) {
            $this->documentations[] = $node->text();
        });

        return (array) $this->documentations;
    }

    public function getScreenshots(Crawler $node): array
    {
        $node->eq(6)->filter('a')->each(function ($node) {
            $this->screenshots[] = $node->link()->getUri();
        });

        return (array)$this->screenshots;
    }

    public function getScreencasts(Crawler $node): mixed
    {
        if(count($node->eq(7)->filter('a')) != 0) {
            $this->screencasts = [];

            $node->eq(7)->filter('a')->each(function ($node) {
                $this->screencasts[] = $node->link()->getUri();
            });
        } else {
            $this->screencasts = null;
        }

        return $this->screencasts;
    }

    public function getDownloadMirrorLinks(Crawler $node): array
    {
        $node->eq(8)->filter('a')->each(function ($node) {
            $this->mirrorLinks[] = $node->link()->getUri();
        });

        return (array) $this->mirrorLinks;
    }

    public function getBugTrackerLinks(Crawler $node): string|null
    {
        $filterText = Str::remove('Bug Tracker ', $node->eq(9)->text());

        $this->bugTracker = Str::contains($filterText, '--') ? $this->bugTracker = null : $this->bugTracker = $filterText;

        return $this->bugTracker;
    }

    public function getRelatedWebsites(Crawler $node): mixed
    {
        if(count($node->eq(10)->filter('a'))){
            $this->relatedWebsites = [];

            $node->eq(10)->filter('a')->each(function ($node) {
                $this->relatedWebsites[] = $node->link()->getUri();
            });
        }else{
            $this->relatedWebsites = null;
        }

        return $this->relatedWebsites;
    }

    public function getReviews(Crawler $node): array
    {
        $node->eq(11)->filter('a')->each(function ($node) {
            $this->reviews[] = $node->link()->getUri();
        });

        return (array) $this->reviews;
    }

    /* Check the skor if exists to anticipate an error */
    public function checkScoreAndAverageRating(Crawler $node): string
    {
        if (count($node->filter('blockquote')->eq(0)->filter('div')->eq(2)) > 0) {
            $score = $node->filter('blockquote')->eq(0)->filter('div')->eq(2)->html();
        } else {
            $score = $node->filter('blockquote')->eq(0)->filter('div')->html();
        }

        return $score . ' from ' . $node->filter('blockquote')->eq(0)->filter('b')->eq(1)->text() . ' reviews';
    }

    /* Check the where to buy or try if exists to anticipate an error */
    public function checkWhereToBuy(Crawler $node): mixed
    {
        if (count($node->eq(12)->filter('a')) != 0) {
            $this->buyOrTry = [];
            $this->buyOrTry['url'] = $node->eq(12)->filter('a')->link()->getUri();
            $this->buyOrTry['text'] = $node->eq(12)->filter('a')->text();
        } else {
            $this->buyOrTry = null;
        }

        return $this->buyOrTry;
    }

    public function recentRelatedNewsAndRealeses(Crawler $node): mixed
    {
        if(count($node->filter('.Background')->eq(13)) != 0){
            $this->newsAndRealeses = [];

            $node->filter('.Background')->eq(13)->filter('a')->each(function ($node) {
                $this->newsAndRealeses[] = [
                    'text' => $node->text(),
                    'url' => $node->link()->getUri(),
                ];
            });
        }else{
            $this->newsAndRealeses = null;
        }

        return $this->newsAndRealeses;
    }
}
