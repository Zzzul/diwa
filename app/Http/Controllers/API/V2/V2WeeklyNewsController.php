<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Services\GoutteClientService;
use App\Services\WeeklyNewsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class V2WeeklyNewsController extends Controller
{
    /**
     * @var WeeklyNewsService WeeklyNewsService
     */
    public WeeklyNewsService $weeklyNewsService;

    /**
     * @var client
     */
    public $client;

    /**
     * @var baseUrl
     */
    public string $baseUrl;

    public function __construct(GoutteClientService $goutteClientService, WeeklyNewsService $weeklyNewsService)
    {
        $this->client = $goutteClientService->setup();
        $this->baseUrl = config('app.distrowatch_url');
        $this->weeklyNewsService = $weeklyNewsService;
    }


    /**
     * @OA\Get(
     *     path="/api/v2/weekly",
     *     tags={"Weekly News"},
     *     summary="Get all weekly news",
     *     description="Warning!, big size response",
     *     operationId="getAllWeeklyNews",
     *     @OA\Response(response="200", description="success")
     * )
     */
    public function index()
    {
        return Cache::remember('weekly-news', now()->addDay(2), function () {
            $crawler = $this->client->request('GET', (string) $this->baseUrl . 'weekly.php');

            $news = $this->weeklyNewsService->getAllWeeklyNews($crawler);

            return response()->json([
                'message' => 'success',
                'news' => $news
            ], Response::HTTP_OK);
        });
    }


    /**
     * @OA\Get(
     *     path="/api/v2/weekly/{id}",
     *     tags={"Weekly News"},
     *     summary="Get weekly news information detail",
     *     description="If {weekly_id} not found, distrowatch.com will return the latest weekly news. make sure {weekly_id} is correct",
     *     operationId="getWeeklyNewsById",
     *     @OA\Response(response="200", description="success"),
     *     @OA\Parameter(
     *          name="id",
     *          description="Weekly News Id",
     *          required=true,
     *          in="path",
     *          example="20220502",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *     ),
     * )
     */
    public function show(int $id)
    {
        return Cache::rememberForever('weekly-news-' . $id, function () use ($id) {
            $crawler = $this->client->request('GET', (string) $this->baseUrl . "weekly.php?issue=$id");

            return response()->json([
                'message' => 'success',
                'title' => $this->weeklyNewsService->getWeeklyNewsTitle($crawler),
                'story' => $this->weeklyNewsService->getWeeklyNewsStory($crawler),
                'content' => $this->weeklyNewsService->getWeeklyNewsContent($crawler)
            ], Response::HTTP_OK);
        });
    }
}
