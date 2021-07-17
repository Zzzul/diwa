<?php

namespace App\Http\Controllers\API;

use Goutte\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

class ParamsController extends Controller
{
    public function rankingParams()
    {
        $client = new Client();

        $url = env('DISTROWATCH_URL');

        $crawler = $client->request('GET', $url);

        $crawler->filter('select')->eq(5)->children()->each(function ($node) {
            if ($node->attr('value') != null && $node->text() != '') {
                $this->params[] = [
                    'slug' => $node->attr('value'),
                    'name' => $node->text(),
                ];
            }
        });

        return response()->json([
            'message' => 'Success.',
            'status_code' => Response::HTTP_OK,
            'params' => $this->params
        ], Response::HTTP_OK);
    }
}
