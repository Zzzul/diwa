<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
                            'url' => url('/api/ranking/{slug}'),
                            'example' => route("ranking.show", 'trending-1'),
                            'list_params' => route("params.ranking"),
                            'note' => 'if {slug} not found, distrowatch.com will return the home page with default ranking(last 6 months). make sure {slug} is correct',
                        ]
                    ],
                ],
                'news' => [
                    'simple' => [
                        'default' => [
                            'url' => route("news"),
                            'note' => 'latest 12 news and 1 sponsor news'
                        ],
                        'custom' => [
                            'url' => 'coming soon',
                            'example' => 'coming soon',
                            'list_params' => 'coming soon',
                            'note' => 'coming soon',
                        ],
                    ],
                    'detail' => [
                        'type' => [
                            'distribution_news' => [
                                'url' => route("news") . '/{news_id}',
                                'example' => route("news.show", 11300),
                                'note' => 'if {news_id} not found, distrowatch.com will return the home page. make sure {news_id} is correct'
                            ],
                            'weekly_news' => [
                                'url' => 'coming soon',
                                'example' => 'coming soon',
                            ],
                        ],
                    ],
                ],
            ],
            'author' => 'Mohammad Zulfahmi',
            'repository' => 'https://github.com/Zzzul/diwa'
        ], Response::HTTP_OK);
    }
}
