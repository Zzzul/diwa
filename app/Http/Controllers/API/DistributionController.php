<?php

namespace App\Http\Controllers\API;

use Goutte\Client;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

class DistributionController extends Controller
{
    private $based_on = [];
    private $architecture = [];
    private $category = [];
    private $desktop = [];
    private $documentation = [];
    private $screenshots = [];
    private $screencasts = [];
    private $download_mirrors = [];
    private $related_websites = [];
    private $reviews = [];
    private $where_to_buy_or_try = [];
    private $recent_related_news_and_releases = [];
    private $average_rating = [];

    private $distribution = '';
    private $description = '';
    private $last_update = '';
    private $origin = '';
    private $status = '';
    private $popularity = '';
    private $homepage = '';
    private $user_forum = '';
    private $alternative_user_forum = '';
    private $os_type = '';
    private $bug_tracker = '';



    public function show($name)
    {
        $client = new Client();

        $url = env('DISTROWATCH_URL') . "table.php?distribution=$name";

        $crawler = $client->request('GET', $url);

        $node = $crawler->filter('h1')->eq(0);
        // dd($node);
        abort_if(count($node) == 0, 404, 'jaja awok');

        $this->distribution = $node->text();
        $this->last_update = Str::remove('Last Update: ', $node->nextAll()->text());

        // os type
        $this->os_type = $crawler->filter('ul')->eq(1)->filter('li')->eq(0)->filter('a')->text();

        // based on
        $crawler->filter('ul')->eq(1)->filter('li')->eq(1)->filter('a')->each(function ($node) {
            $this->based_on[] = Str::remove('Based on: ', $node->text());
        });

        // origin
        $this->origin = $crawler->filter('ul')->eq(1)->filter('li')->eq(2)->filter('a')->text();

        // architecture
        $crawler->filter('ul')->eq(1)->filter('li')->eq(3)->filter('a')->each(function ($node) {
            $this->architecture[] = Str::remove('Architecture: ', $node->text());
        });

        // desktop
        $crawler->filter('ul')->eq(1)->filter('li')->eq(4)->filter('a')->each(function ($node) {
            $this->desktop[] = Str::remove('Desktop: ', $node->text());
        });

        // Category
        $crawler->filter('ul')->eq(1)->filter('li')->eq(5)->filter('a')->each(function ($node) {
            $this->category[] = Str::remove('Category: ', $node->text());
        });

        // origin
        $this->status = Str::remove('Status: ', $crawler->filter('ul')->eq(1)->filter('li')->eq(6)->text());

        // popularity
        $this->popularity = Str::remove('Popularity: ', $crawler->filter('ul')->eq(1)->filter('li')->eq(7)->text());

        // description (soon)
        $remove_ul_text = Str::remove($crawler->filter('.TablesTitle')->filter('ul')->text(), $crawler->filter('.TablesTitle')->text());

        $this->description = Str::before($remove_ul_text, ' Popularity (hits per day)');

        // summary
        // homepage of distribution url
        $this->homepage = $crawler->filter('.Background')->eq(1)->filter('a')->link()->getUri();

        // distribution user forum url
        $this->user_forum = count($crawler->filter('.Background')->eq(3)->filter('a')) != 0 ? $crawler->filter('.Background')->eq(3)->filter('a')->link()->getUri() : '';

        $this->alterbative_user_forum = $crawler->filter('.Background')->eq(4)->text();

        $crawler->filter('.Background')->eq(5)->filter('a')->each(function ($node) {
            $this->documentation[] = $node->text();
        });

        $crawler->filter('.Background')->eq(6)->filter('a')->each(function ($node) {
            $this->screenshots[] = $node->link()->getUri();
        });

        $crawler->filter('.Background')->eq(7)->filter('a')->each(function ($node) {
            $this->screencasts[] = $node->link()->getUri();
        });

        $crawler->filter('.Background')->eq(8)->filter('a')->each(function ($node) {
            $this->download_mirrors[] = $node->link()->getUri();
        });

        $this->bug_tracker = Str::remove('Bug Tracker ', $crawler->filter('.Background')->eq(9)->text());

        $crawler->filter('.Background')->eq(10)->filter('a')->each(function ($node) {
            $this->related_websites[] = $node->link()->getUri();
        });

        $crawler->filter('.Background')->eq(11)->filter('a')->each(function ($node) {
            $this->reviews[] = $node->link()->getUri();
        });

        if (count($crawler->filter('.Background')->eq(12)->filter('a')) != 0) {
            $this->where_to_buy_or_try['url'] = $crawler->filter('.Background')->eq(12)->filter('a')->link()->getUri();
            $this->where_to_buy_or_try['text'] = $crawler->filter('.Background')->eq(12)->filter('a')->text();
        } else {
            $this->where_to_buy_or_try['url'] =  '';
            $this->where_to_buy_or_try['text'] = '';
        }


        $crawler->filter('.Background')->eq(13)->filter('a')->each(function ($node) {
            $this->recent_related_news_and_releases[] =  [
                'text' => $node->text(),
                'url' => $node->link()->getUri(),
            ];
        });

        $this->average_rating = $crawler->filter('blockquote')->eq(0)->filter('div')->eq(2)->html() . ' from ' . $crawler->filter('blockquote')->eq(0)->filter('div')->eq(2)->nextAll()->html() . ' reviews';


        return response()->json([
            'message' => 'Success',
            'status_code' => Response::HTTP_OK,
            'distribution' => $this->distribution,
            'last_update' => $this->last_update,
            'description' => $this->description,
            'os_type' => $this->os_type,
            'based_on' => $this->based_on,
            'origin' => $this->origin,
            'architecture' => $this->architecture,
            'desktop' => $this->desktop,
            'category' => $this->category,
            'status' => $this->status,
            'popularity' => $this->popularity,
            'homepage' => $this->homepage,
            'user_forum' => $this->user_forum,
            'alternative_user_forum' => $this->alternative_user_forum,
            'documentation' => $this->documentation,
            'screenshots' => $this->screenshots,
            'screencasts' => $this->screencasts,
            'download_mirrors' => $this->download_mirrors,
            'bug_tracker' => $this->bug_tracker,
            'related_websites' => $this->related_websites,
            'reviews' => $this->reviews,
            'where_to_buy_or_try' => $this->where_to_buy_or_try,
            'recent_related_news_and_releases' => $this->recent_related_news_and_releases,
            'average_rating' => $this->average_rating,
        ], Response::HTTP_OK);
    }
}
