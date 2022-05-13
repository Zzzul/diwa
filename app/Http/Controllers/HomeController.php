<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
        /**
     * @OA\Info(
     *      version="2.0",
     *      title="Diwa",
     *      description="Diwa is an unofficial simple API from [distrowatch.com](https://distrowatch.com/)",
     *      @OA\Contact(
     *          email="mzulfahmi807@gmail.com"
     *      ),
     *      @OA\License(
     *          name="MIT",
     *          url="https://github.com/Zzzul/diwa/blob/main/LICENSE"
     *      )
     * )
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
