<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class v2HomeController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/v2",
     *   tags={"Home"},
     *   summary="Get all v2 avaiable endpoints",
     *   operationId="v2-ome",
     *   @OA\Response(response=200, description="success")
     * )
     *
     *  @OA\Tag(
     *     name="Home",
     *     description="Home"
     * )
     */
    public function __invoke()
    {
        return response()->json([
            'message' => 'success',
            'docs' => url('/docs'),
            'source' => 'https://github.com/Zzzul/diwa',
            'endpoints' => [
                'distributions' => [
                    'all' => route('v2.distributions.index'),
                    'detail' => [
                        'url' => url('/api/v2/distributions/{name}'),
                        'example' => route("v2.distributions.show", 'mx'),
                    ],
                    'rankings' => [
                        'default' => [
                            'url' => route("v2.rankings.index"),
                            'note' => 'Top 100 rankings of last 6 months'
                        ],
                        'custom' => [
                            'url' => route("v2.rankings.index") . '/{slug}',
                            'example' => route("v2.rankings.show", 'trending-1'),
                            'avaiable_params' => route("v2.params.rankings"),
                            'note' => 'If {slug} not found, distrowatch.com will return the home page with default ranking(last 6 months). make sure {slug} is correct',
                        ]
                    ],
                    'search' => [
                        'avaiable_params' => route("v2.params.search"),
                        'url' => url('/api/v2/search?ostype={os_type}&category={distribution_category}&origin={country_of_origin}&basedon={based_on}&notbasedon={not_based_on}&desktop={desktop_environment}&architecture={architecture}&package={package_manager}&rolling={release_model}&isosize={install_media_size}&netinstall={install_mehthod}&language={multi_language_support}&defaultinit={software_init}&status={status}#simple'),
                        'example' => route("v2.search.index", 'notbasedon=None&ostype=Linux&category=All&origin=All&basedon=Ubuntu&desktop=Xfce&architecture=All&package=All&rolling=All&isosize=All&netinstall=All&language=All&defaultinit=All&status=Active'),
                        'note' => 'If one of the {params} not found, distrowatch.com will used default params(All/None)'
                    ]
                ],
                'news' => [
                    'distribution_news' => [
                        'all' => [
                            'default' => [
                                'url' => route("v2.news.index"),
                                'note' => 'Latest 12 news and 1 sponsor news'
                            ],
                            'custom' => [
                                'url' => route("v2.news.filter") . '?distribution=distribution_name&release=realease&month=month&year=year',
                                'example' => route("v2.news.filter") . '?distribution=ubuntu&release=stable&month=all&year=2021',
                                'avaiable_params' => route("v2.params.news"),
                                'note' => 'If one of the {params} not found, distrowatch.com will return the home page with default params(all). make sure all {params} are correct',
                            ],
                        ],
                        'detail' => [
                            'url' => route("v2.news.index") . '/{news_id}',
                            'example' => route("v2.news.show", 11531),
                            'note' => 'If {news_id} not found, distrowatch.com will return the home page. make sure {news_id} is correct'
                        ],
                    ],
                    'weekly_news' => [
                        'all' => [
                            'url' => route("v2.weekly.index"),
                            'note' => 'Big size response!'
                        ],
                        'detail' => [
                            'url' => route("v2.weekly.index") . '/{weekly_id}',
                            'example' => route("v2.weekly.show", 20220502),
                            'note' => 'If {weekly_id} not found, distrowatch.com will return the latest weekly news. make sure {weekly_id} is correct'
                        ],
                    ],
                ],
            ],
        ], Response::HTTP_OK);
    }
}
