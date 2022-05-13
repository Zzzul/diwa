<?php

namespace App\Http\Controllers\API\V1;

use Goutte\Client;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Services\GoutteClientService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class DistributionNewsController extends Controller
{
    protected array $news = [];
    protected array $body = [];
    protected array $recent_related_news_and_releases = [];
    protected array $distribution_summary = [];

    protected string $about = '';
    protected string $screenshots = '';
    protected string $distrowatch_news_url = '';
    protected string $headline = '';
    protected string $thumbnail = '';
    protected string $date = '';
    protected string $distribution_detail_url = '';
    protected string $news_detail_url = '';
    protected string $distrowatch_distribution_detail_url = '';

    protected bool $sponsor = false;

    /**
     * @var client
     */
    protected $client;

    /**
     * @var baseUrl
     */
    protected string $baseUrl;

    public function __construct(GoutteClientService $goutteClientService)
    {
        $this->client = $goutteClientService->setup();
        $this->baseUrl = config('app.distrowatch_url');
    }

    public function index()
    {
        return Cache::remember('allNews', 3600, function () {
            $crawler = $this->client->request('GET', (string) $this->baseUrl);

            $crawler->filter('.News1 > table')->reduce(function ($node, $i) {
                $this->getNewsData($node, $i);
            });

            return response()->json([
                'message' => 'Success',

                'news' => $this->news
            ], Response::HTTP_OK);
        });
    }

    public function show($id)
    {
        return Cache::remember('DistributionNews' . $id, 86400, function () use ($id) {
            $crawler = $this->client->request('GET', (string) $this->baseUrl . "?newsid=$id");

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

    public function filterNews(Request $request)
    {
        $distribution = $request->distribution ?? 'all';
        $release = $request->release ?? 'all';
        $month = $request->month ?? 'all';
        $year = $request->year ?? 'all';

        $cache_name = Str::camel($distribution . ' ' . $release . ' ' . $month . ' ' . $year);

        return Cache::remember($cache_name, 86400, function () use ($distribution, $release, $month, $year) {
            $crawler = $this->client->request('GET', (string) $this->baseUrl . "?distribution=$distribution&release=$release&month=$month&year=$year");

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
    public function getNewsData($node, $i)
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

    public function getAllValueForDistributionSummary($summary)
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

    public function getScreenshot($filter_info_class_element)
    {
        $filter_info_class_element->eq(31)->each(function ($node) {
            $this->screenshots = config('app.distrowatch_url') . $node->filter('img')->attr('src');
        });

        return $this->screenshots;
    }

    public function getRecentRelatedNews($filter_td_element)
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

    public function getDistributionDetailUrl()
    {
        $this->distribution_detail_url = route("distribution.show", Str::remove('https://distrowatch.com/', $this->distrowatch_distribution_detail_url));

        return $this->distribution_detail_url;
    }

    public function getAboutText($filter_td_element)
    {
        $filter_td_element->eq(1)->each(function ($node) {
            $this->about = $node->text();
        });

        return $this->about;
    }
}
