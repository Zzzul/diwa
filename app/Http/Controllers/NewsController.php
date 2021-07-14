<?php

namespace App\Http\Controllers;

use Goutte\Client;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NewsController extends Controller
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

        $page->filter('.News1 > table')->reduce(function ($node, $i) {
            $sponsor = false;

            if ($i >= 1) {
                if ($i < 13) {
                    $headline = $node->children()->filter('td')->nextAll()->text();

                    $newsUrl = $node->children()->filter('td')->nextAll()->filter('a')->nextAll()->link()->getUri();
                } else {
                    // sponsor news
                    $newsUrl = $node->children()->filter('td')->nextAll()->filter('a')->link()->getUri();

                    $headline = $node->children()->filter('td')->nextAll()->filter('a')->text();

                    $sponsor = true;
                }

                $news = [
                    'headline' => Str::remove('NEW • ', $headline),
                    'date' => $node->children()->filter('td')->text(),
                    'news_url' => $newsUrl,

                    'thumbnail' => env('DISTROWATCH_URL') . $node->children()->filter('.NewsLogo')->filter('a')->eq(0)->children('img')->attr('src'),

                    'distribution_url' => $node->children()->filter('.NewsLogo')->filter('a')->eq(0)->link()->getUri(),

                    'description' => $node->children()->filter('.NewsText')->text(),
                    'sponsor' => $sponsor
                ];

                array_push($this->results, $news);
            }
        });

        return response()->json([
            'message' => 'Success',
            'status_code' => Response::HTTP_OK,
            'news' => $this->results
        ]);
    }
}
