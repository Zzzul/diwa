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


    /**
     * @OA\Get(
     *     path="/api/weekly",
     *     tags={"News"},
     *     summary="Get all weekly news",
     *     description="Warning!, big size response",
     *     operationId="getAllWeeklyNews",
     *     @OA\Response(response="200", description="Success")
     * )
     */
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


    /**
     * @OA\Get(
     *     path="/api/weekly/{id}",
     *     tags={"News"},
     *     summary="Get weekly news information detail",
     *     description="If {weekly_id} not found, distrowatch.com will return the latest weekly news. make sure {weekly_id} is correct",
     *     operationId="getWeeklyNewsById",
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Parameter(
     *          name="id",
     *          description="Weekly News Id",
     *          required=true,
     *          in="path",
     *          example="20210719",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *     ),
     * )
     */
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
