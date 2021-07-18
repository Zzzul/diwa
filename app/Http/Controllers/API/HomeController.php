<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

class HomeController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke()
    {
        return response()->json([
            'message' => 'Success',
            'status_code' => Response::HTTP_OK,
            'source' => env('DISTROWATCH_URL'),
            'docs' => 'coming soon',
            'endpoints' => [
                'distribution' => [
                    'all' => route("distribution.index"),
                    'detail' => [
                        'url' => url('/api/distribution/{name}'),
                        'example' => route("distribution.show", 'mx'),
                    ],
                    'ranking' => [
                        'default' => [
                            'url' => route("ranking.index"),
                            'note' => 'top 100 rankings of last 6 months'
                        ],
                        'custom' => [
                            'url' => route("news.index") . '/{slug}',
                            'example' => route("ranking.show", 'trending-1'),
                            'list_params' => route("params.ranking"),
                            'note' => 'if {slug} not found, distrowatch.com will return the home page with default ranking(last 6 months). make sure {slug} is correct',
                        ]
                    ],
                ],
                'news' => [
                    'distribution_news' => [
                        'all' => [
                            'default' => [
                                'url' => route("news.index"),
                                'note' => 'latest 12 news and 1 sponsor news'
                            ],
                            'custom' => [
                                'url' => route("news.filteringNews", ['distribution' => 'mx', 'release' => 'stable', 'month' => 'August', 'year' => 2021]),
                                'example' => route("news.index") . '/filter/distribution={distribution}&release={release}&month={month}&year={year}',
                                'list_params' => route("params.news"),
                                'note' => 'if one of the {params} not found, distrowatch.com will return the home page with default params(all). make sure all {params} are correct',
                            ],
                        ],
                        'detail' => [
                            'url' => route("news.index") . '/{news_id}',
                            'example' => route("news.show", 11300),
                            'note' => 'if {news_id} not found, distrowatch.com will return the home page. make sure {news_id} is correct'
                        ],
                    ],
                    'weekly_news' => [
                        'all' => ['url' => 'coming soon'],
                        'detail' => [
                            'url' => 'coming soon',
                            'example' => 'coming soon',
                        ]
                    ],
                ],
            ],
            'author' => 'Mohammad Zulfahmi',
            'repository' => 'https://github.com/Zzzul/diwa'
        ], Response::HTTP_OK);
    }
}
