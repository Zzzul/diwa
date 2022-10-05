<?php

namespace App\Services;

use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class RankingService
{
    public function __construct(
        public array $rankings = [],
        public string $distrowatch_distribution_detail_url = '',
        public string $distribution_detail_url = '',
        public string $data_span = '',
    ) {
    }

    public function getAll(Crawler $node): array
    {
        $node->each(function ($node, $i) {

            $this->distrowatch_distribution_detail_url =  $node->filter('a')->link()->getUri();

            $this->distribution_detail_url = route("v2.distributions.show", Str::remove('https://distrowatch.com/', $this->distrowatch_distribution_detail_url));

            $this->rankings[] = [
                'no' => $i + 1,
                'distribution' => $node->filter('a')->text(),
                'hits_per_day_count' => intval($node->nextAll()->text()),
                'hits_yesterday_count' => intval(Str::remove('Yesterday: ', $node->nextAll()->attr('title'))),
                'detail' => [
                    'distrowatch' => $this->distrowatch_distribution_detail_url,
                    'diwa' => $this->distribution_detail_url,
                ],
            ];
        });

        return $this->rankings;
    }

    public function getDataSpan(Crawler $node, string $slug): string
    {
        $node->each(function ($node) use ($slug) {
            if ($node->attr('value') == $slug) {
                $this->data_span = $node->text();
            }
        });

        return $this->data_span != '' ? $this->data_span = $this->data_span : $this->data_span = 'Last 6 months';
    }
}
