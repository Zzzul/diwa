<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Goutte\Client;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class DistributionNewsController extends Controller
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
    private $distrowatch_distribution_detail_url = '';
    private $distrowatch_news_url = '';
    private $sponsor = false;

    /**
     * @OA\Get(
     *     path="/api/news",
     *     tags={"News"},
     *     summary="Get all distribution and weekly news",
     *     operationId="getAllDistributionNews",
     *     description="Return latest 12 news and 1 sponsor news",
     *     @OA\Response(response="200", description="Success")
     * )
     *
     *  @OA\Tag(
     *     name="News",
     *     description="API Endpoints of News"
     * )
     */
    public function index()
    {
        $client = new Client();

        $url = env('DISTROWATCH_URL');

        $crawler = $client->request('GET', $url);

        $crawler->filter('.News1 > table')->reduce(function ($node, $i) {
            $this->getNewsData($node, $i);
        });

        return response()->json([
            'message' => 'Success',
            'status_code' => Response::HTTP_OK,
            'news' => $this->news
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *     path="/api/news/{id}",
     *     tags={"News"},
     *     summary="Get Distribution News information detail",
     *     description="If {news_id} not found, distrowatch.com will return the home page. make sure {news_id} is correct",
     *     operationId="getDistributionNewsById",
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Parameter(
     *          name="id",
     *          description="News Id",
     *          required=true,
     *          in="path",
     *          example="11302",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *     ),
     * )
     */
    public function show($id)
    {
        $client = new Client();

        $url = env('DISTROWATCH_URL') . "?newsid=$id";

        $crawler = $client->request('GET', $url);

        // body
        $crawler->filter('.News1 > table')->eq(1)->each(function ($node) {
            $headline = $node->children()->filter('td')->nextAll()->text();

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
        // end of body

        // about
        $crawler->filter('.Background > td')->eq(1)->each(function ($node) {
            $this->about = $node->text();
        });

        // distribution_summary
        $summary = $crawler->filter('.Info')->eq(4)->filter('.Info');
        $this->distribution_summary['distribution'] = $summary->eq(2)->text();

        $this->distribution_summary['home_page'] = $summary->eq(4)->text();

        $this->distribution_summary['mailing_lists'] = $summary->eq(6)->text() != '--' ? $summary->eq(6)->text() : '';

        $this->distribution_summary['user_forum'] = $summary->eq(8)->text() != '--' ? $summary->eq(8)->text() : '';

        $this->distribution_summary['alternative_user_forum'] = $summary->eq(10)->text() != '--' ? $summary->eq(10)->text() : '';

        $summary->eq(12)->filter('a')->each(function ($node) {
            $this->distribution_summary['documentation'][] = $node->link()->getUri();
        });

        $summary->eq(14)->filter('a')->each(function ($node) {
            $this->distribution_summary['gallery'][] = $node->link()->getUri();
        });

        $summary->eq(16)->filter('a')->each(function ($node) {
            if (count($node) > 0) {
                $this->distribution_summary['screencasts'][] = $node->link()->getUri();
            } else {
                $this->distribution_summary['screencasts'] = '';
            }
        });

        $summary->eq(18)->filter('a')->each(function ($node) {
            if (count($node) > 0) {
                $this->distribution_summary['download_mirrors'][] = $node->link()->getUri();
            } else {
                $this->distribution_summary['download_mirrors'] = '';
            }
        });

        $this->distribution_summary['bug_tracker'] = $summary->eq(20)->filter('a')->link()->getUri();

        $summary->eq(22)->filter('a')->each(function ($node) {
            $this->distribution_summary['related_websites'][] = $node->link()->getUri();
        });

        $summary->eq(24)->filter('a')->each(function ($node) {
            $this->distribution_summary['reviews'][] = $node->link()->getUri();
        });

        if (count($summary->eq(26)->filter('a')) > 0) {
            $this->distribution_summary['where_to_buy']['text'] = $summary->eq(26)->filter('a')->text();
            $this->distribution_summary['where_to_buy']['url'] = $summary->eq(26)->filter('a')->link()->getUri();
        } else {
            $this->distribution_summary['where_to_buy'] = '';
        }
        // end of summary

        // Screenshots
        $crawler->filter('.Info')->eq(31)->each(function ($node) {
            $this->screenshots = env('DISTROWATCH_URL') . $node->filter('img')->attr('src');
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
        // end of recent_related_news_and_releases

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
                'summary' => $this->distribution_summary,
                'screenshots' => $this->screenshots,
                'recent_related_news_and_releases' => $this->recent_related_news_and_releases,
            ]
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *     path="/api/news/filter/distribution={name}&release={release}&month={month}&year={year}",
     *     tags={"News"},
     *     summary="Get specific distribution news",
     *     description="If one of the {params} not found, distrowatch.com will return the home page with default params(all). make sure all {params} are correct",
     *     operationId="FilterDistributionNews",
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Parameter(
     *          name="name",
     *          description="Distribution Name",
     *          required=true,
     *          in="path",
     *          example="mx",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="release",
     *          description="Release Version",
     *          required=true,
     *          in="path",
     *          example="stable",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="month",
     *          description="Month",
     *          required=true,
     *          in="path",
     *          example="april",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="year",
     *          description="Year",
     *          required=true,
     *          in="path",
     *          example="2021",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *     ),
     * )
     */
    public function filteringNews(
        $distribution = 'all',
        $release = 'all',
        $month = 'all',
        $year = 'all'
    ) {
        $client = new Client();

        $url = env('DISTROWATCH_URL') . "?distribution=$distribution&release=$release&month=$month&year=$year";

        $crawler = $client->request('GET', $url);

        $crawler->filter('.News1 > table')->reduce(function ($node, $i) {
            $this->getNewsData($node, $i);
        });

        return response()->json([
            'message' => 'Success',
            'status_code' => Response::HTTP_OK,
            'news' => $this->news
        ], Response::HTTP_OK);
    }

    public function getNewsData($node, $i)
    {
        if ($i >= 1) {
            $headline = $node->children()->filter('td')->nextAll()->text();

            if (count($node->children()->filter('td')->nextAll()->filter('a')->nextAll()) > 0) {
                // distribution and weekly news
                $news_detail_url_params = $node->children()->filter('td')->nextAll()->filter('a')->nextAll()->attr('href');

                $this->distrowatch_news_url = $node->children()->filter('td')->nextAll()->filter('a')->nextAll()->link()->getUri();
            } else {
                // sponsor news
                $this->distrowatch_news_url =  $node->children()->filter('td')->nextAll()->filter('a')->link()->getUri();

                $news_detail_url_params = '3cx&ver=pbx';

                $this->sponsor = true;
            }


            if (Str::contains($headline, 'DistroWatch Weekly') || Str::contains($headline, 'Featured Distribution')) {
                // sponsor and weekly news
                if (Str::contains($headline, 'Featured Distribution')) {
                    $this->news_detail_url = route("distribution.show", $news_detail_url_params);
                }

                if (Str::contains($headline, 'DistroWatch Weekly')) {
                    $this->news_detail_url = route("weekly.show", Str::after($news_detail_url_params, 'weekly.php?issue='));
                }

                $this->distribution_detail_url = '';
                $this->distrowatch_distribution_detail_url =  '';
            } else {
                // distribution news
                $this->news_detail_url = route("news.show", $news_detail_url_params);

                $href = $node->children()->filter('.NewsLogo')->filter('a')->attr('href');
                $this->distribution_detail_url = route("distribution.show", $href);

                $this->distrowatch_distribution_detail_url = $node->children()->filter('.NewsLogo')->filter('a')->eq(0)->link()->getUri();
            };

            $this->news[] = [
                'headline' => Str::remove('NEW • ', $headline),
                'date' => $node->children()->filter('td')->text(),

                'thumbnail' => env('DISTROWATCH_URL') . $node->children()->filter('.NewsLogo')->filter('a')->eq(0)->children('img')->attr('src'),

                'distrowatch_news_url' => $this->distrowatch_news_url,
                'distrowatch_distribution_detail_url' => $this->distrowatch_distribution_detail_url,

                'news_detail_url' =>  $this->news_detail_url,
                'distribution_detail_url' => $this->distribution_detail_url,

                'body' => $node->children()->filter('.NewsText')->text(),
                'sponsor' => $this->sponsor
            ];
        }
    }
}
