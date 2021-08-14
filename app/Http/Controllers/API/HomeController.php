<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class HomeController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api",
     *   tags={"Home"},
     *   summary="Get all endpoints and info about this API",
     *   operationId="home",
     *   @OA\Response(response=200, description="Success")
     * )
     *
     *  @OA\Tag(
     *     name="Home",
     *     description="API Endpoints of Home"
     * )
     */
    public function __invoke()
    {
        return Cache::rememberForever('home',  function () {
            return response()->json([
                'message' => 'Success',
                'status_code' => Response::HTTP_OK,
                'source' => env('DISTROWATCH_URL'),
                'docs' => url('/documentation'),
                'repository' => 'https://github.com/Zzzul/diwa',
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
                                'note' => 'Top 100 rankings of last 6 months'
                            ],
                            'custom' => [
                                'url' => route("ranking.index") . '/{slug}',
                                'example' => route("ranking.show", 'trending-1'),
                                'list_params' => route("params.ranking"),
                                'note' => 'If {slug} not found, distrowatch.com will return the home page with default ranking(last 6 months). make sure {slug} is correct',
                            ]
                        ],
                        'search' => [
                            'list_params' => route("params.search"),
                            'url' => url('/api/search?ostype={os_type}&category={distribution_category}&origin={country_of_origin}&basedon={based_on}&notbasedon={not_based_on}&desktop={desktop_environment}&architecture={architecture}&package={package_manager}&rolling={release_model}&isosize={install_media_size}&netinstall={install_mehthod}&language={multi_language_support}&defaultinit={software_init}&status={status}#simple'),
                            'example' => route("search.index", 'ostype=Linux&category=All&origin=All&basedon=All&notbasedon=Ubuntu&desktop=Xfce&architecture=All&package=All&rolling=All&isosize=All&netinstall=All&language=All&defaultinit=All&status=Active#simple'),
                            'note' => 'If one of the {params} not found, distrowatch.com will used default params(All)'
                        ]
                    ],
                    'news' => [
                        'distribution_news' => [
                            'all' => [
                                'default' => [
                                    'url' => route("news.index"),
                                    'note' => 'Latest 12 news and 1 sponsor news'
                                ],
                                'custom' => [
                                    'url' => route("news.filter") . '?distribution=distribution_name&release=realease&month=month&year=year',

                                    'example' => route("news.filter") . '?distribution=ubuntu&release=stable&month=all&year=2021',

                                    'list_params' => route("params.news"),

                                    'note' => 'If one of the {params} not found, distrowatch.com will return the home page with default params(all). make sure all {params} are correct',
                                ],
                            ],
                            'detail' => [
                                'url' => route("news.index") . '/{news_id}',
                                'example' => route("news.show", 11300),
                                'note' => 'If {news_id} not found, distrowatch.com will return the home page. make sure {news_id} is correct'
                            ],
                        ],
                        'weekly_news' => [
                            'all' => [
                                'url' => route("weekly.index"),
                                'note' => 'Warning - big size response'
                            ],
                            'detail' => [
                                'url' => route("weekly.index") . '/{weekly_id}',
                                'example' => route("weekly.show", 20210719),
                                'note' => 'If {weekly_id} not found, distrowatch.com will return the latest weekly news. make sure {weekly_id} is correct'
                            ],
                        ],
                    ],
                ],
            ], Response::HTTP_OK);
        });
    }
}
