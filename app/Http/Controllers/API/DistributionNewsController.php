<?php

namespace App\Http\Controllers\API;

use Goutte\Client;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class DistributionNewsController extends Controller
{
    private array $news = [];
    private array $body = [];
    private array $recent_related_news_and_releases = [];
    private array $distribution_summary = [];

    private string $about = '';
    private string $screenshots = '';
    private string $distrowatch_news_url = '';
    private string $headline = '';
    private string $thumbnail = '';
    private string $date = '';
    private string $distribution_detail_url = '';
    private string $news_detail_url = '';
    private string $distrowatch_distribution_detail_url = '';

    private bool $sponsor = false;

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
        // 1 hour
        $seocnds = 3600;

        return Cache::remember('allNews', $seocnds, function () {
            $client = new Client();

            $url = config('app.distrowatch_url');

            $crawler = $client->request('GET', $url);

            $crawler->filter('.News1 > table')->reduce(function ($node, $i) {
                $this->getNewsData($node, $i);
            });

            return response()->json([
                'message' => 'Success',

                'news' => $this->news
            ], Response::HTTP_OK);
        });
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
        // i day
        $seocnds = 86400;

        return Cache::remember('DistributionNews' . $id, $seocnds, function () use ($id) {
            $client = new Client();

            $url = config('app.distrowatch_url') . "?newsid=$id";

            $crawler = $client->request('GET', $url);

            $crawler->filter('.News1 > table')->eq(1)->each(function ($node) {
                $headline = $node->children()->filter('td')->nextAll()->text();

                $this->distrowatch_news_url = $node->children()->filter('td')->nextAll()->filter('a')->eq(1)->link()->getUri();

                $this->date = $node->children()->filter('td')->text();

                $this->headline = Str::remove('NEW • ', $headline);

                $this->thumbnail = config('app.distrowatch_url') . $node->children()->filter('.NewsLogo')->filter('a')->eq(0)->children('img')->attr('src');

                $this->distrowatch_distribution_detail_url = $node->children()->filter('.NewsLogo')->filter('a')->eq(0)->link()->getUri();

                $this->body = [
                    'text' => $node->children()->filter('.NewsText')->text(),
                    'html' => $node->children()->filter('.NewsText')->html()
                ];
            });

            $filter_td_element = $crawler->filter('.Background > td');

            $filter_info_class_element = $crawler->filter('.Info');

            $this->about = $this->getAboutText($filter_td_element);

            $this->distribution_summary = $this->getAllValueForDistributionSummary($filter_info_class_element->eq(4)->filter('.Info'));

            $this->screenshots = $this->getScreenshot($filter_info_class_element);

            $this->recent_related_news_and_releases = $this->getRecentRelatedNews($filter_td_element);

            $this->distribution_detail_url = $this->getDistributionDetailUrl();

            return response()->json([
                'message' => 'Success',
                'distrowatch_news_url' => $this->distrowatch_news_url,
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
        });
    }

    /**
     * @OA\Get(
     *     path="/api/filter/news",
     *     tags={"News"},
     *     summary="Get specific distribution news",
     *     description="If one of the {params} not found, distrowatch.com will return the home page with default params(all). make sure all {params} are correct",
     *     operationId="FilterDistributionNews",
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Parameter(
     *          name="name",
     *          description="Distribution Name",
     *          required=true,
     *          in="query",
     *          example="ubuntu",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="release",
     *          description="Release Version",
     *          required=true,
     *          in="query",
     *          example="stable",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="month",
     *          description="Month",
     *          required=true,
     *          in="query",
     *          example="all",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="year",
     *          description="Year",
     *          required=true,
     *          in="query",
     *          example="2021",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *     ),
     * )
     */
    public function filterNews(Request $request)
    {
        // i day
        $seocnds = 86400;

        $distribution = $request->distribution ?? 'all';
        $release = $request->release ?? 'all';
        $month = $request->month ?? 'all';
        $year = $request->year ?? 'all';

        $cache_name = Str::camel($distribution . ' ' . $release . ' ' . $month . ' ' . $year);

        return Cache::remember($cache_name, $seocnds, function () use ($distribution, $release, $month, $year) {
            $client = new Client();

            $url = config('app.distrowatch_url') . "?distribution=$distribution&release=$release&month=$month&year=$year";

            $crawler = $client->request('GET', $url);

            $crawler->filter('.News1 > table')->reduce(function ($node, $i) {
                $this->getNewsData($node, $i);
            });

            return response()->json([
                'message' => 'Success',

                'news' => $this->news
            ], Response::HTTP_OK);
        });
    }

    /**
     * Scrap datas used DOM (for index and show method)
     */
    private function getNewsData($node, $i)
    {
        if ($i >= 1) {
            $headline = $node->children()->filter('td')->nextAll()->text();

            if (Str::contains($headline, 'DistroWatch Weekly')) {
                // Weekly news
                $news_detail_url_params = $node->children()->filter('td')->nextAll()->filter('a')->nextAll()->attr('href');

                $this->news_detail_url = route("weekly.show", Str::after($news_detail_url_params, 'weekly.php?issue='));

                $this->distrowatch_news_url = $node->children()->filter('td')->nextAll()->filter('a')->nextAll()->link()->getUri();

                $this->distribution_detail_url = '';
                $this->distrowatch_distribution_detail_url =  '';
            } elseif (Str::contains($headline, 'Featured Distribution')) {
                // sponsor news
                $href = Str::after($node->children()->filter('.NewsLogo')->filter('a')->attr('href'), '?distribution=');

                $this->distribution_detail_url = route("distribution.show", $href);

                $this->distrowatch_distribution_detail_url = $node->children()->filter('.NewsLogo')->filter('a')->eq(0)->link()->getUri();

                $this->sponsor = true;

                $this->distrowatch_news_url = '';
                $this->news_detail_url = '';
            } else {
                // distribution news
                $news_detail_url_params = $node->children()->filter('td')->nextAll()->filter('a')->nextAll()->attr('href');

                $href = Str::after($node->children()->filter('.NewsLogo')->filter('a')->attr('href'), '?distribution=');

                $this->news_detail_url = route("news.show", $news_detail_url_params);

                $this->distribution_detail_url = route("distribution.show", $href);

                $this->distrowatch_distribution_detail_url = $node->children()->filter('.NewsLogo')->filter('a')->eq(0)->link()->getUri();

                $this->distrowatch_news_url = $node->children()->filter('td')->nextAll()->filter('a')->nextAll()->link()->getUri();
            }

            $this->news[] = [
                'headline' => Str::remove('NEW • ', $headline),
                'date' => $node->children()->filter('td')->text(),

                'thumbnail' => config('app.distrowatch_url') . $node->children()->filter('.NewsLogo')->filter('a')->eq(0)->children('img')->attr('src'),

                'distrowatch_news_url' => $this->distrowatch_news_url,
                'distrowatch_distribution_detail_url' => $this->distrowatch_distribution_detail_url,

                'news_detail_url' =>  $this->news_detail_url,
                'distribution_detail_url' => $this->distribution_detail_url,

                'body' => $node->children()->filter('.NewsText')->text(),
                'sponsor' => $this->sponsor
            ];
        }
    }

    private function getAllValueForDistributionSummary($summary)
    {
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

        return $this->distribution_summary;
    }

    private function getScreenshot($filter_info_class_element)
    {
        $filter_info_class_element->eq(31)->each(function ($node) {
            $this->screenshots = config('app.distrowatch_url') . $node->filter('img')->attr('src');
        });

        return $this->screenshots;
    }

    private function getRecentRelatedNews($filter_td_element)
    {
        $filter_td_element->eq(0)->each(function ($node) {
            $node->filter('a')->each(function ($item, $i) {
                $this->recent_related_news_and_releases[] = [
                    'text' => $item->text(),
                    'url' => $item->link()->getUri()
                ];
            });
        });

        return $this->recent_related_news_and_releases;
    }

    private function getDistributionDetailUrl()
    {
        $this->distribution_detail_url = route("distribution.show", Str::remove('https://distrowatch.com/', $this->distrowatch_distribution_detail_url));

        return $this->distribution_detail_url;
    }

    private function getAboutText($filter_td_element)
    {
        $filter_td_element->eq(1)->each(function ($node) {
            $this->about = $node->text();
        });

        return $this->about;
    }
}
