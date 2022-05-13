<?php

namespace App\Http\Controllers\API\V1;

use Goutte\Client;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Services\GoutteClientService;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class WeeklyNewsController extends Controller
{
    private $lists = [];
    private $content = [];
    private $title = '';
    private $story = '';

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
        return Cache::remember('allWeeklyNews', 86400, function () {
            $crawler = $this->client->request('GET', (string) $this->baseUrl . 'weekly.php');

            $crawler->filter('.List')->each(function ($node) {
                $url = $node->filter('a')->link()->getUri();
                $this->lists[] = [
                    'distrowatch_weekly_detail_url' => $url,
                    'weekly_detail_url' => route("weekly.show", Str::after($url, '?issue=')),
                    'title' => Str::remove('• ', $node->text())
                ];
            });

            return response()->json([
                'message' => 'Success',
                'lists' => $this->lists
            ], Response::HTTP_OK);
        });
    }

    public function show($id)
    {
        // 1 day
        $seocnds = 86400;

        return Cache::remember('weeklyNews' . $id, $seocnds, function () use ($id) {
            $crawler = $this->client->request('GET', (string) $this->baseUrl . "weekly.php?issue=$id");

            // title
            $this->title = $crawler->filter('.rTitle')->text();

            // stoty
            $remove_ul_text = Str::remove($crawler->filter('.rStory')->filter('ul')->text(), $crawler->filter('.rStory')->text());

            $this->story = Str::before($remove_ul_text, 'Content:  Listen to the Podcast');

            $crawler->filter('.rStory')->eq(0)->filter('ul')->filter('a')->each(function ($node) {
                $this->content[] = [
                    'url' =>  $node->link()->getUri(),
                    'text' => $node->text()
                ];
            });

            return response()->json([
                'message' => 'Success',
                'news' => [
                    'title' => $this->title,
                    'story' => $this->story,
                    'content' => $this->content
                ]
            ], Response::HTTP_OK);
        });
    }
}
