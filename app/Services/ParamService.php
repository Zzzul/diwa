<?php

namespace App\Services;

use Symfony\Component\DomCrawler\Crawler;

class ParamService
{
    public function __construct(
        public array $distributions = [],
        public array $releases = [],
        public array $years = [],
        public array $months = []
    ) {
        //
    }

    public function getRankings(Crawler $node): array
    {
        $node->each(function ($node) {
            if ($node->attr('value') != null && $node->text() != '') {
                $this->distributions[] = [
                    'slug' => $node->attr('value'),
                    'text' => $node->text(),
                ];
            }
        });

        return $this->distributions;
    }

    public function getDistributions(Crawler $node): array
    {
        $node->eq(0)->filter('option')->each(function ($node) {
            $this->distributions[] = [
                'slug' => $node->attr('value'),
                'text' => $node->text(),
            ];
        });

        return $this->distributions;
    }

    public function getReleases(Crawler $node): array
    {
        $node->eq(1)->filter('option')->each(function ($node) {
            $this->releases[] = [
                'slug' => $node->attr('value'),
                'text' => $node->text(),
            ];
        });

        return $this->releases;
    }

    public function getMonths(Crawler $node): array
    {
        $node->eq(2)->filter('option')->each(function ($node) {
            $this->months[] = [
                'slug' => $node->attr('value'),
                'text' => $node->text(),
            ];
        });

        return $this->months;
    }

    public function getYears(Crawler $node): array
    {
        $node->eq(3)->filter('option')->each(function ($node) {
            $this->years[] = [
                'slug' => $node->attr('value'),
                'text' => $node->text(),
            ];
        });

        return $this->years;
    }
}
