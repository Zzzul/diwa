<?php

namespace App\Http\Controllers\API;

use Goutte\Client;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class DistributionController extends Controller
{
    private array $all_distribution = [];
    private array $based_on = [];
    private array $architecture = [];
    private array $category = [];
    private array $desktop = [];
    private array $documentation = [];
    private array $screenshots = [];
    private array $screencasts = [];
    private array $download_mirrors = [];
    private array $related_websites = [];
    private array $reviews = [];
    private array $where_to_buy_or_try = [];
    private array $recent_related_news_and_releases = [];

    private string $average_rating = '';
    private string $distribution = '';
    private string $about = '';
    private string $last_update = '';
    private string $origin = '';
    private string $status = '';
    private string $popularity = '';
    private string $homepage = '';
    private string $user_forum = '';
    private string $alternative_user_forum = '';
    private string $os_type = '';
    private string $bug_tracker = '';
    private string $mailing_list = '';

    /**
     * @OA\Get(
     *     path="/api/distribution",
     *     tags={"Distribution"},
     *     summary="Get all Distribution",
     *     operationId="getAllDistribution",
     *     @OA\Response(response="200", description="Success")
     * )
     *
     *  @OA\Tag(
     *     name="Distribution",
     *     description="API Endpoints of Distribution"
     * )
     */
    public function index()
    {
        return Cache::rememberForever('allDistribution', function () {

            $client = new Client();

            $url = config('app.distrowatch_url');

            $crawler = $client->request('GET', $url);

            $crawler->filter('select')->children()->each(function ($node, $i) {
                if ($i > 0) {
                    $this->all_distribution[] = [
                        'slug' => $node->attr('value'),
                        'name' => $node->text(),
                        'distrowatch_distribution_detail_url' => config('app.distrowatch_url') . $node->attr('value'),
                        'distribution_detail_url' => route("distribution.show", $node->attr('value')),
                    ];
                }
            });

            return response()->json([
                'message' => 'Success.',
                'distributions' => $this->all_distribution
            ], Response::HTTP_OK);
        });
    }

    /**
     * @OA\Get(
     *     path="/api/distribution/{name}",
     *     tags={"Distribution"},
     *     summary="Get distribution information detail",
     *     description="If {name} not found, will return 404",
     *     operationId="getDistributionById",
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Parameter(
     *          name="name",
     *          description="Distribution Name",
     *          required=true,
     *          in="path",
     *          example="ubuntu",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     )
     * )
     */
    public function show($slug)
    {
        // 1 day
        $seocnds = 86400;

        $cache_name = Str::camel('distribution ' . $slug);

        return Cache::remember($cache_name, $seocnds, function () use ($slug) {
            $client = new Client();

            $url = config('app.distrowatch_url') . "table.php?distribution=$slug";

            $crawler = $client->request('GET', $url);

            // Check for not found
            $node = $crawler->filter('h1')->eq(0);

            if (count($node) == 0) {
                return response()->json([
                    'message' => 'distribution not found.',
                    'home' => route("home")
                ], Response::HTTP_NOT_FOUND);
            }

            $filter_ul_element = $crawler->filter('ul');

            $filter_background_class = $crawler->filter('.Background');

            return response()->json([
                'message' => 'Success',
                'distribution' => $this->getDistributionName($node),
                'last_update' => $this->getLastUpdate($node),
                'os_type' => $this->getOsType($filter_ul_element),
                'origin' => $this->getOrigin($filter_ul_element),
                'about' =>  $this->getAboutText($crawler),
                'based_on' => $this->getBasedOn($filter_ul_element),
                'architecture' => $this->getArchitectures($filter_ul_element),
                'desktop' => $this->getDesktopTypes($filter_ul_element),
                'category' => $this->getCategories($filter_ul_element),
                'status' => $this->getStatus($filter_ul_element),
                'popularity' => $this->getPopularity($filter_ul_element),
                'homepage' => $this->getHomepageUrl($filter_background_class),
                'mailing_list' => $this->getMailingList($filter_background_class),
                'user_forum' => $this->getUserForumUrl($filter_background_class),
                'alternative_user_forum' => $this->getAlternativeUserForum($filter_background_class),
                'documentation' => $this->getDocumentation($filter_background_class),
                'screenshots' => $this->getScreenshots($filter_background_class),
                'screencasts' => $this->getScreencasts($filter_background_class),
                'download_mirrors' => $this->getDownloadMirrorLinks($filter_background_class),
                'bug_tracker' => $this->getBugTrackerLinks($filter_background_class),
                'related_websites' => $this->getRelatedWebsites($filter_background_class),
                'reviews' => $this->getReviews($filter_background_class),
                'where_to_buy_or_try' => $this->checkWhereToBuy($filter_background_class),
                'recent_related_news_and_releases' => $this->recentRelatedNewsAndReleases($filter_background_class),
                'average_rating' => $this->checkSkorAndAverageRating($crawler),
            ], Response::HTTP_OK);
        });
    }

    private function getDistributionName($node)
    {
        $this->distribution = $node->text();

        return $this->distribution;
    }

    private function getOsType($filter_ul_element)
    {
        $this->os_type = $filter_ul_element->eq(1)->filter('li')->eq(0)->filter('a')->text();

        return $this->os_type;
    }

    private function getLastUpdate($node)
    {
        $this->last_update = Str::remove('Last Update: ', $node->nextAll()->text());

        return $this->last_update;
    }

    private function getAboutText($crawler)
    {
        $remove_ul_text = Str::remove($crawler->filter('.TablesTitle')->filter('ul')->text(), $crawler->filter('.TablesTitle')->text());

        $remove_popularity_text = Str::before($remove_ul_text, ' Popularity (hits per day)');

        $this->about = Str::after($remove_popularity_text, ' UTC  ');

        return $this->about;
    }

    private function getBasedOn($filter_ul_element)
    {
        $filter_ul_element->eq(1)->filter('li')->eq(1)->filter('a')->each(function ($node) {
            $this->based_on[] = Str::remove('Based on: ', $node->text());
        });

        return $this->based_on;
    }

    private function getOrigin($filter_ul_element)
    {
        $this->origin = $filter_ul_element->eq(1)->filter('li')->eq(2)->filter('a')->text();

        return $this->origin;
    }

    private function getArchitectures($filter_ul_element)
    {
        $filter_ul_element->eq(1)->filter('li')->eq(3)->filter('a')->each(function ($node) {
            $this->architecture[] = Str::remove('Architecture: ', $node->text());
        });

        return $this->architecture;
    }

    private function getDesktopTypes($filter_ul_element)
    {
        $filter_ul_element->eq(1)->filter('li')->eq(4)->filter('a')->each(function ($node) {
            $this->desktop[] = Str::remove('Desktop: ', $node->text());
        });

        return $this->desktop;
    }

    private function getCategories($filter_ul_element)
    {
        $filter_ul_element->eq(1)->filter('li')->eq(5)->filter('a')->each(function ($node) {
            $this->category[] = Str::remove('Category: ', $node->text());
        });

        return $this->category;
    }

    private function getStatus($filter_ul_element)
    {
        $this->status = Str::remove('Status: ', $filter_ul_element->eq(1)->filter('li')->eq(6)->text());

        return $this->status;
    }

    private function getPopularity($filter_ul_element)
    {
        $this->popularity = Str::remove('Popularity: ', $filter_ul_element->eq(1)->filter('li')->eq(7)->text());

        return $this->popularity;
    }

    private function getHomepageUrl($filter_background_class)
    {
        $this->homepage = $filter_background_class->eq(1)->filter('a')->link()->getUri();
        return $this->homepage;
    }

    private function getMailingList($filter_background_class)
    {
        $filter_text = Str::remove('Mailing Lists  ', $filter_background_class->eq(2)->text());

        $this->mailing_list = Str::contains($filter_text, '--') ? $this->mailing_list = '' : $this->mailing_list = $filter_text;

        return $this->mailing_list;
    }

    private function getUserForumUrl($filter_background_class)
    {
        $this->user_forum = count($filter_background_class->eq(3)->filter('a')) != 0 ?
            $filter_background_class->eq(3)->filter('a')->link()->getUri() : '';

        return $this->user_forum;
    }

    private function getAlternativeUserForum($filter_background_class)
    {
        $this->alternative_user_forum = $filter_background_class->eq(4)->text();

        return $this->alternative_user_forum;
    }

    private function getDocumentation($filter_background_class)
    {
        $filter_background_class->eq(5)->filter('a')->each(function ($node) {
            $this->documentation[] = $node->text();
        });

        return $this->documentation;
    }

    private function getScreenshots($filter_background_class)
    {
        $filter_background_class->eq(6)->filter('a')->each(function ($node) {
            $this->screenshots[] = $node->link()->getUri();
        });

        return $this->screenshots;
    }

    private function getScreencasts($filter_background_class)
    {
        $filter_background_class->eq(7)->filter('a')->each(function ($node) {
            $this->screencasts[] = $node->link()->getUri();
        });

        return $this->screencasts;
    }

    private function getDownloadMirrorLinks($filter_background_class)
    {
        $filter_background_class->eq(8)->filter('a')->each(function ($node) {
            $this->download_mirrors[] = $node->link()->getUri();
        });

        return $this->download_mirrors;
    }

    private function getBugTrackerLinks($filter_background_class)
    {
        $filter_text = Str::remove('Bug Tracker ', $filter_background_class->eq(9)->text());

        $this->bug_tracker = Str::contains($filter_text, '--') ? $this->bug_tracker = '' : $this->bug_tracker = $filter_text;

        return $this->bug_tracker;
    }

    private function getRelatedWebsites($filter_background_class)
    {
        $filter_background_class->eq(10)->filter('a')->each(function ($node) {
            $this->related_websites[] = $node->link()->getUri();
        });

        return $this->related_websites;
    }

    private function getReviews($filter_background_class)
    {
        $filter_background_class->eq(11)->filter('a')->each(function ($node) {
            $this->reviews[] = $node->link()->getUri();
        });

        return $this->reviews;
    }

    /* Check the skor if exists to anticipate an error */
    private function checkSkorAndAverageRating($crawler)
    {
        if (count($crawler->filter('blockquote')->eq(0)->filter('div')->eq(2)) > 0) {
            $skor = $crawler->filter('blockquote')->eq(0)->filter('div')->eq(2)->html();
        } else {
            $skor = $crawler->filter('blockquote')->eq(0)->filter('div')->html();
        }

        $this->average_rating = $skor . ' from ' . $crawler->filter('blockquote')->eq(0)->filter('b')->eq(1)->text() . ' reviews';

        return $this->average_rating;
    }

    /* Check the where to buy or try if exists to anticipate an error */
    private function checkWhereToBuy($filter_background_class)
    {
        if (count($filter_background_class->eq(12)->filter('a')) != 0) {
            $this->where_to_buy_or_try['url'] = $filter_background_class->eq(12)->filter('a')->link()->getUri();
            $this->where_to_buy_or_try['text'] = $filter_background_class->eq(12)->filter('a')->text();
        } else {
            $this->where_to_buy_or_try['url'] =  '';
            $this->where_to_buy_or_try['text'] = '';
        }
    }

    private function recentRelatedNewsAndReleases($filter_background_class)
    {
        // bug: manjaro and some distro dont show recent_related_news_and_releases
        $filter_background_class->filter('.Background')->eq(13)->filter('a')->each(function ($node) {
            $this->recent_related_news_and_releases[] =  [
                'text' => $node->text(),
                'url' => $node->link()->getUri(),
            ];
        });

        return $this->recent_related_news_and_releases;
    }
}
