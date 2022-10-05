<?php

namespace App\Services;

use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class SearchService
{
    public function __construct(
        public array $params = [],
        public array $searchResults = []
    ) {
        //
    }

    public function getAll(Crawler $selectElement): array
    {
        $selectElement->eq(0)->children()->each(function ($node) {
            $this->params['os_types'][] = $node->attr('value');
        });

        $selectElement->eq(1)->children()->each(function ($node) {
            $this->params['distribution_categories'][] = $node->attr('value');
        });

        $selectElement->eq(2)->children()->each(function ($node) {
            $this->params['country_of_origins'][] = $node->attr('value');
        });

        $selectElement->eq(3)->children()->each(function ($node) {
            $this->params['based_ons'][] = $node->attr('value');
        });

        $selectElement->eq(4)->children()->each(function ($node) {
            $this->params['not_based_ons'][] = $node->attr('value');
        });

        $selectElement->eq(5)->children()->each(function ($node) {
            $this->params['desktop_environments'][] = $node->attr('value');
        });

        $selectElement->eq(6)->children()->each(function ($node) {
            $this->params['architectures'][] = $node->attr('value');
        });

        $selectElement->eq(7)->children()->each(function ($node) {
            $this->params['package_managements'][] = $node->attr('value');
        });

        $selectElement->eq(8)->children()->each(function ($node) {
            $this->params['releases_model'][] = $node->attr('value');
        });

        $selectElement->eq(9)->children()->each(function ($node) {
            $this->params['install_media_sizes'][] = $node->attr('value');
        });

        $selectElement->eq(10)->children()->each(function ($node) {
            $this->params['install_methods'][] = $node->attr('value');
        });

        $selectElement->eq(11)->children()->each(function ($node) {
            $this->params['multi_language_supports'][] = $node->attr('value');
        });

        $selectElement->eq(12)->children()->each(function ($node) {
            $this->params['init_softwares'][] = $node->attr('value');
        });

        $selectElement->eq(13)->children()->each(function ($node) {
            $this->params['status'][] = $node->attr('value');
        });

        return $this->params;
    }

    public function search(Crawler $node): array
    {
        $node->each(function ($node, $i) {
            if ($i >= 15) {
                $ranking = Str::after($node->text(), '(');

                $url = $node->filter('a')->link()->getUri();

                $this->searchResults[] = [
                    'distribution' => $node->filter('a')->text(),
                    'ranking' => intval(Str::remove(')', $ranking)),
                    'detail' => [
                        'distrowatch' => $url,
                        'diwa' => route("distribution.show", Str::after($url, 'com/')),
                    ],
                ];
            }
        });

        return $this->searchResults;
    }
}
