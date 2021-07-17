<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Goutte\Client;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class NewsController extends Controller
{
    private $news = [];
    private $body = [];
    private $recent_related_news_and_releases = [];
    private $distribution_summary = [];
    private $about = '';
    private $screenshots = '';
    private $distrowatch_url_news = '';
    private $headline = '';
    private $thumbnail = '';
    private $date = '';
    private $distribution_detail_url = '';
    private $news_detail_url = '';

    public function index()
    {
        $client = new Client();

        $url = env('DISTROWATCH_URL');

        $crawler = $client->request('GET', $url);

        $crawler->filter('.News1 > table')->reduce(function ($node, $i) {
            $sponsor = false;

            if ($i >= 1) {
                if ($i < 13) {
                    $headline = $node->children()->filter('td')->nextAll()->text();

                    $distrowatch_news_url = $node->children()->filter('td')->nextAll()->filter('a')->nextAll()->link()->getUri();

                    $news_detail_url = $node->children()->filter('td')->nextAll()->filter('a')->nextAll()->attr('href');
                } else {
                    // sponsor news
                    $distrowatch_news_url = $node->children()->filter('td')->nextAll()->filter('a')->link()->getUri();

                    $news_detail_url = $node->children()->filter('td')->nextAll()->filter('a')->attr('href');

                    $headline = $node->children()->filter('td')->nextAll()->filter('a')->text();

                    $sponsor = true;
                }

                if (Str::contains($headline, 'DistroWatch Weekly') || Str::contains($headline, 'Featured Distribution')) {
                    $this->news_detail_url = '';
                    $this->distribution_detail_url = '';
                } else {
                    $this->news_detail_url = route("news.show", $news_detail_url);

                    $href = $node->children()->filter('.NewsLogo')->filter('a')->attr('href');
                    $this->distribution_detail_url =  route("distribution.show", $href);
                };

                $this->news[] = [
                    'headline' => Str::remove('NEW • ', $headline),
                    'date' => $node->children()->filter('td')->text(),

                    'thumbnail' => env('DISTROWATCH_URL') . $node->children()->filter('.NewsLogo')->filter('a')->eq(0)->children('img')->attr('src'),

                    'distrowatch_news_url' => $distrowatch_news_url,
                    'distrowatch_distribution_detail_url' => $node->children()->filter('.NewsLogo')->filter('a')->eq(0)->link()->getUri(),

                    'news_detail_url' =>  $this->news_detail_url,
                    'distribution_detail_url' => $this->distribution_detail_url,

                    'body' => $node->children()->filter('.NewsText')->text(),
                    'sponsor' => $sponsor
                ];
            }
        });

        return response()->json([
            'message' => 'Success',
            'status_code' => Response::HTTP_OK,
            'news' => $this->news
        ], Response::HTTP_OK);
    }

    public function show($id)
    {
        $client = new Client();

        $url = env('DISTROWATCH_URL') . "?newsid=$id";

        $crawler = $client->request('GET', $url);

        // body
        $crawler->filter('.News1 > table')->eq(1)->each(function ($node) {
            $headline = $node->children()->filter('td')->nextAll()->text();

            // if news_id not found
            $this->distrowatch_url_news = $node->children()->filter('td')->nextAll()->filter('a')->eq(1)->link()->getUri();

            $this->date = $node->children()->filter('td')->text();

            $this->headline = Str::remove('NEW • ', $headline);

            $this->thumbnail = env('DISTROWATCH_URL') . $node->children()->filter('.NewsLogo')->filter('a')->eq(0)->children('img')->attr('src');

            $this->distrowatch_distribution_detail_url = $node->children()->filter('.NewsLogo')->filter('a')->eq(0)->link()->getUri();

            $this->body = [
                'text' => $node->children()->filter('.NewsText')->text(),
                'html' => $node->children()->filter('.NewsText')->html()
            ];
        });

        // recent_related_news_and_releases
        $crawler->filter('.Background > td')->eq(0)->each(function ($node) {
            $node->filter('a')->each(function ($item, $i) {
                $this->recent_related_news_and_releases[] = [
                    'text' => $item->text(),
                    'url' => $item->link()->getUri()
                ];
            });
        });

        // about
        $crawler->filter('.Background > td')->eq(1)->each(function ($node) {
            $this->about = $node->text();
        });

        // distribution_summary
        $crawler->filter('.Info')->eq(4)->each(function ($node) {
            $node->filter('.Background')->each(function ($item) {
                $item->filter('td')->each(function ($td) use ($item) {
                    $this->distribution_summary[Str::snake($item->filter('th')->text())] = $td->text();
                });
            });
        });

        // Screenshots
        $crawler->filter('.Info')->eq(31)->each(function ($node) {
            $this->screenshots = env('DISTROWATCH_URL') . $node->filter('img')->attr('src');
        });

        $this->distribution_detail_url = route("distribution.show", Str::remove('https://distrowatch.com/', $this->distrowatch_distribution_detail_url));

        return response()->json([
            'message' => 'Success',
            'status_code' => Response::HTTP_OK,
            'distrowatch_news_url' => $this->distrowatch_url_news,
            'distrowatch_distribution_detail_url' => $this->distrowatch_distribution_detail_url,
            'distribution_detail_url' => $this->distribution_detail_url,
            'news_detail' => [
                'headline' => $this->headline,
                'date' => $this->date,
                'thumbnail' => $this->thumbnail,
                'about' => $this->about,
                'body' => $this->body,
                'recent_related_news_and_releases' => $this->recent_related_news_and_releases,
                'summary' => $this->distribution_summary,
                'screenshots' => $this->screenshots
            ]
        ], Response::HTTP_OK);
    }
}
