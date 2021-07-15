<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Goutte\Client;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NewsController extends Controller
{
    private $all_news = [];
    private $news_detail = [];
    private $recent_related_news_and_releases = [];
    private $distribution_summary = [];
    private $about_distribution = '';
    private $screenshots = '';

    public function index()
    {
        $client = new Client();

        $url = env('DISTROWATCH_URL', 'https://distrowatch.com/');

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

                $this->all_news[] = [
                    'headline' => Str::remove('NEW • ', $headline),
                    'date' => $node->children()->filter('td')->text(),

                    'thumbnail' => env('DISTROWATCH_URL') . $node->children()->filter('.NewsLogo')->filter('a')->eq(0)->children('img')->attr('src'),

                    'distrowatch_news_url' => $distrowatch_news_url,
                    'news_detail_url' => route("news.show", $news_detail_url),

                    'distribution_url' => $node->children()->filter('.NewsLogo')->filter('a')->eq(0)->link()->getUri(),

                    'description' => $node->children()->filter('.NewsText')->text(),
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

        // body
        $crawler = $client->request('GET', $url);

        $crawler->filter('.News1 > table')->eq(1)->each(function ($node, $i) {
            $headline = $node->children()->filter('td')->nextAll()->text();

            $this->news_detail = [
                'headline' => Str::remove('NEW • ', $headline),
                'date' => $node->children()->filter('td')->text(),

                'thumbnail' => env('DISTROWATCH_URL') . $node->children()->filter('.NewsLogo')->filter('a')->eq(0)->children('img')->attr('src'),

                'distribution_url' => $node->children()->filter('.NewsLogo')->filter('a')->eq(0)->link()->getUri(),

                'description' => $node->children()->filter('.NewsText')->text(),
                'description_html' => $node->children()->filter('.NewsText')->html()
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

        // about_distribution
        $crawler->filter('.Background > td')->eq(1)->each(function ($node, $i) {
            $this->about_distribution = $node->text();
        });

        // distribution_summary
        $crawler->filter('.Info')->eq(4)->each(function ($node, $i) {
            $node->filter('.Background')->each(function ($item) {
                $item->filter('td')->each(function ($td) use ($item) {
                    $this->distribution_summary[Str::snake($item->filter('th')->text())] = $td->text();
                });
            });
        });

        // Screenshots
        $crawler->filter('.Info')->eq(31)->each(function ($node, $i) {
            $this->screenshots = env('DISTROWATCH_URL') . $node->filter('img')->attr('src');
        });

        return response()->json([
            'message' => 'Success',
            'status_code' => Response::HTTP_OK,
            'distrowatch_news_url' => $url,
            'news_detail' => [
                'body' => $this->news_detail,
                'about_distribution' => $this->about_distribution,
                'recent_related_news_and_releases' => $this->recent_related_news_and_releases,
                'distribution_summary' => $this->distribution_summary,
                'screenshots' => $this->screenshots
            ]
        ], Response::HTTP_OK);
    }
}
