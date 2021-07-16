<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Goutte\Client;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RankingController extends Controller
{
    private $rankings = [];

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $client = new Client();

        $url = env('DISTROWATCH_URL');

        $crawler = $client->request('GET', $url);

        $crawler->filter('.phr2')->each(function ($node, $i) use ($url) {
            $this->rankings[] = [
                'no' => $i + 1,
                'distribution' => $node->filter('a')->text(),
                'distrowatch_distribution_url' => $node->filter('a')->link()->getUri(),
                'distribution_url' => 'Coming soon',
                // hits per day
                'hpd' => [
                    'count' => intval($node->nextAll()->text()),
                    'status' => ($node->nextAll()->filter('img')->attr('alt') == '<')
                        ? 'adown' : (($node->nextAll()->filter('img')->attr('alt') == '>')
                            ? 'aup' : 'alevel'),

                    'alt' => $node->nextAll()->filter('img')->attr('alt'),

                    'image' => $url . $node->nextAll()->filter('img')->attr('src'),
                ],
                'hits_yesterday_count' => intval(Str::remove('Yesterday: ', $node->nextAll()->attr('title'))),
            ];
        });

        return response()->json([
            'message' => 'Success',
            'status_code' => Response::HTTP_OK,
            'hpd' => 'Hits Per Day',
            'rankings' => $this->rankings
        ]);
    }
}
