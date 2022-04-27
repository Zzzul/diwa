<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke()
    {
        return response()->json([
            'message' => 'success',
            'versions' => [
                'v1' => route('home.v1'),
                'v2' => route('v2.home')
            ]
        ]);
    }
}
