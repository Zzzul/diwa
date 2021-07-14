<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Goutte\Client;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RankingController extends Controller
{
    private $results = [];
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $client = new Client();

        $url = env('DISTROWATCH_URL', 'https://distrowatch.com/');

        $page = $client->request('GET', $url);

        $page->filter('.phr2')->each(function ($node, $i) use ($url) {
            $rankings = [
                'no' => $i + 1,
                'distribution' => $node->filter('a')->text(),
                'url' => $node->filter('a')->link()->getUri(),
                'hits_per_day_count' => intval($node->nextAll()->text()),

                'hits_yesterday_count' => intval(Str::remove('Yesterday: ', $node->nextAll()->attr('title'))),

                'hpd' => ($node->nextAll()->filter('img')->attr('alt') == '<')
                    ? 'adown' : (($node->nextAll()->filter('img')->attr('alt') == '>')
                        ? 'aup' : 'alevel'),

                'hpd_alt' => $node->nextAll()->filter('img')->attr('alt'),

                'hpd_image' => $url . $node->nextAll()->filter('img')->attr('src'),
            ];

            array_push($this->results, $rankings);
        });

        return response()->json([
            'message' => 'Success',
            'status_code' => Response::HTTP_OK,
            'hpd' => 'Hits Per Day',
            'rankings' => $this->results
        ]);
    }
}
