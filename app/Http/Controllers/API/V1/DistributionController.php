<?php

namespace App\Http\Controllers\API\V1;

use Goutte\Client;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Services\GoutteClientService;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class DistributionController extends Controller
{
    protected array $all_distribution = [];
    protected array $based_on = [];
    protected array $architecture = [];
    protected array $category = [];
    protected array $desktop = [];
    protected array $documentation = [];
    protected array $screenshots = [];
    protected array $screencasts = [];
    protected array $download_mirrors = [];
    protected array $related_websites = [];
    protected array $reviews = [];
    protected array $where_to_buy_or_try = [];
    protected array $recent_related_news_and_releases = [];

    protected string $average_rating = '';
    protected string $distribution = '';
    protected string $about = '';
    protected string $last_update = '';
    protected string $origin = '';
    protected string $status = '';
    protected string $popularity = '';
    protected string $homepage = '';
    protected string $user_forum = '';
    protected string $alternative_user_forum = '';
    protected string $os_type = '';
    protected string $bug_tracker = '';
    protected string $mailing_list = '';

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
        return Cache::rememberForever('allDistribution', function () {
            $crawler = $this->client->request('GET', (string) $this->baseUrl);

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

    public function show($slug)
    {
        $cache_name = Str::camel('distribution ' . $slug);

        return Cache::remember($cache_name, 86400, function () use ($slug) {
            $crawler = $this->client->request('GET', (string) $this->baseUrl . "table.php?distribution=$slug");

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

    public function getDistributionName($node)
    {
        $this->distribution = $node->text();

        return $this->distribution;
    }

    public function getOsType($filter_ul_element)
    {
        $this->os_type = $filter_ul_element->eq(1)->filter('li')->eq(0)->filter('a')->text();

        return $this->os_type;
    }

    public function getLastUpdate($node)
    {
        $this->last_update = Str::remove('Last Update: ', $node->nextAll()->text());

        return $this->last_update;
    }

    public function getAboutText($crawler)
    {
        $remove_ul_text = Str::remove($crawler->filter('.TablesTitle')->filter('ul')->text(), $crawler->filter('.TablesTitle')->text());

        $remove_popularity_text = Str::before($remove_ul_text, ' Popularity (hits per day)');

        $this->about = Str::after($remove_popularity_text, ' UTC  ');

        return $this->about;
    }

    public function getBasedOn($filter_ul_element)
    {
        $filter_ul_element->eq(1)->filter('li')->eq(1)->filter('a')->each(function ($node) {
            $this->based_on[] = Str::remove('Based on: ', $node->text());
        });

        return $this->based_on;
    }

    public function getOrigin($filter_ul_element)
    {
        $this->origin = $filter_ul_element->eq(1)->filter('li')->eq(2)->filter('a')->text();

        return $this->origin;
    }

    public function getArchitectures($filter_ul_element)
    {
        $filter_ul_element->eq(1)->filter('li')->eq(3)->filter('a')->each(function ($node) {
            $this->architecture[] = Str::remove('Architecture: ', $node->text());
        });

        return $this->architecture;
    }

    public function getDesktopTypes($filter_ul_element)
    {
        $filter_ul_element->eq(1)->filter('li')->eq(4)->filter('a')->each(function ($node) {
            $this->desktop[] = Str::remove('Desktop: ', $node->text());
        });

        return $this->desktop;
    }

    public function getCategories($filter_ul_element)
    {
        $filter_ul_element->eq(1)->filter('li')->eq(5)->filter('a')->each(function ($node) {
            $this->category[] = Str::remove('Category: ', $node->text());
        });

        return $this->category;
    }

    public function getStatus($filter_ul_element)
    {
        $this->status = Str::remove('Status: ', $filter_ul_element->eq(1)->filter('li')->eq(6)->text());

        return $this->status;
    }

    public function getPopularity($filter_ul_element)
    {
        $this->popularity = Str::remove('Popularity: ', $filter_ul_element->eq(1)->filter('li')->eq(7)->text());

        return $this->popularity;
    }

    public function getHomepageUrl($filter_background_class)
    {
        $this->homepage = $filter_background_class->eq(1)->filter('a')->link()->getUri();
        return $this->homepage;
    }

    public function getMailingList($filter_background_class)
    {
        $filter_text = Str::remove('Mailing Lists  ', $filter_background_class->eq(2)->text());

        $this->mailing_list = Str::contains($filter_text, '--') ? $this->mailing_list = '' : $this->mailing_list = $filter_text;

        return $this->mailing_list;
    }

    public function getUserForumUrl($filter_background_class)
    {
        $this->user_forum = count($filter_background_class->eq(3)->filter('a')) != 0 ?
            $filter_background_class->eq(3)->filter('a')->link()->getUri() : '';

        return $this->user_forum;
    }

    public function getAlternativeUserForum($filter_background_class)
    {
        $this->alternative_user_forum = $filter_background_class->eq(4)->text();

        return $this->alternative_user_forum;
    }

    public function getDocumentation($filter_background_class)
    {
        $filter_background_class->eq(5)->filter('a')->each(function ($node) {
            $this->documentation[] = $node->text();
        });

        return $this->documentation;
    }

    public function getScreenshots($filter_background_class)
    {
        $filter_background_class->eq(6)->filter('a')->each(function ($node) {
            $this->screenshots[] = $node->link()->getUri();
        });

        return $this->screenshots;
    }

    public function getScreencasts($filter_background_class)
    {
        $filter_background_class->eq(7)->filter('a')->each(function ($node) {
            $this->screencasts[] = $node->link()->getUri();
        });

        return $this->screencasts;
    }

    public function getDownloadMirrorLinks($filter_background_class)
    {
        $filter_background_class->eq(8)->filter('a')->each(function ($node) {
            $this->download_mirrors[] = $node->link()->getUri();
        });

        return $this->download_mirrors;
    }

    public function getBugTrackerLinks($filter_background_class)
    {
        $filter_text = Str::remove('Bug Tracker ', $filter_background_class->eq(9)->text());

        $this->bug_tracker = Str::contains($filter_text, '--') ? $this->bug_tracker = '' : $this->bug_tracker = $filter_text;

        return $this->bug_tracker;
    }

    public function getRelatedWebsites($filter_background_class)
    {
        $filter_background_class->eq(10)->filter('a')->each(function ($node) {
            $this->related_websites[] = $node->link()->getUri();
        });

        return $this->related_websites;
    }

    public function getReviews($filter_background_class)
    {
        $filter_background_class->eq(11)->filter('a')->each(function ($node) {
            $this->reviews[] = $node->link()->getUri();
        });

        return $this->reviews;
    }

    /* Check the skor if exists to anticipate an error */
    public function checkSkorAndAverageRating($crawler)
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
    public function checkWhereToBuy($filter_background_class)
    {
        if (count($filter_background_class->eq(12)->filter('a')) != 0) {
            $this->where_to_buy_or_try['url'] = $filter_background_class->eq(12)->filter('a')->link()->getUri();
            $this->where_to_buy_or_try['text'] = $filter_background_class->eq(12)->filter('a')->text();
        } else {
            $this->where_to_buy_or_try['url'] =  '';
            $this->where_to_buy_or_try['text'] = '';
        }
    }

    public function recentRelatedNewsAndReleases($filter_background_class)
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
