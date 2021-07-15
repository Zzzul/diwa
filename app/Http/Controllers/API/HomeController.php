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
    public function __invoke(Request $request)
    {
        return response()->json([
            'message' => 'Success',
            'status_code' => Response::HTTP_OK,
            'distrowatch' => env('DISTROWATCH_URL'),
            'endpoints' => [
                'rankings' => route("rankings"),
                'all_news' => route("news"),
                'news_detail' => [
                    'distribution_news' => [
                        'url' => route("news") . '/{news_id}',
                        'example' => route("news.show", 11300),
                    ],
                    'weekly_news' => [
                        'url' => 'Coming soon',
                        'example' => 'Coming soon',
                    ]
                ],
                'distribution_detail' => [
                    'url' => 'Coming soon',
                    'example' => 'Coming soon',
                ],
            ],
            'author' => 'Mohammad Zulfahmi',
            'source' => 'https://github.com/Zzzul/diwa'
        ], Response::HTTP_OK);
    }
}
