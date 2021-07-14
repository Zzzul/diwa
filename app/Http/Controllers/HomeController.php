<?php

namespace App\Http\Controllers;

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
            'endpoints' => [
                'rankings' => route("rankings"),
                'news' => route("news"),
                'per_news' => [
                    'url' => env('DISTROWATCH_URL') . '{news_id}',
                    'example' => env('DISTROWATCH_URL') . 11300
                ],
                'per_distribution' => [
                    'url' => env('DISTROWATCH_URL') . '{distribution}',
                    'example' => env('DISTROWATCH_URL') . 'mx'
                ],
            ],
            'author' => 'Mohammad Zulfahmi',
            'source' => 'https://github.com/Zzzul/diwa'
        ], Response::HTTP_OK);
    }
}
