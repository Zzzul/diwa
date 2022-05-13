<?php

namespace App\Http\Controllers\API\V2;

use Goutte\Client;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Services\GoutteClientService;
use App\Services\NewsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class v2NewsController extends Controller
{
    /**
     * @var client
     */
    protected $client;

    /**
     * @var NewsService $newsService
     */
    protected $newsService;

    /**
     * @var baseUrl
     */
    protected string $baseUrl;

    public function __construct(GoutteClientService $goutteClientService, NewsService $newsService)
    {
        $this->client = $goutteClientService->setup();
        $this->newsService = $newsService;
        $this->baseUrl = config('app.distrowatch_url');
    }

    /**
     * @OA\Get(
     *     path="/api/v2/news",
     *     tags={"News"},
     *     summary="Get all distribution and weekly news",
     *     operationId="getAllDistributionNews",
     *     description="Return latest 12 news and 1 sponsor news",
     *     @OA\Response(response="200", description="success")
     * )
     *
     *  @OA\Tag(
     *     name="News",
     *     description="API Endpoints of News"
     * )
     */
    public function index()
    {
        return Cache::remember('v2-news', now()->addDay(), function () {
            $crawler = $this->client->request('GET', (string) $this->baseUrl);

            $news = $this->newsService->getNews($crawler->filter('.News1 > table'));

            return response()->json([
                'message' => 'success',
                'news' => $news
            ], Response::HTTP_OK);
        });
    }

    /**
     * @OA\Get(
     *     path="/api/v2/news/{id}",
     *     tags={"News"},
     *     summary="Get Distribution News information detail",
     *     description="If {news_id} not found, distrowatch.com will return the home page. make sure {news_id} is correct",
     *     operationId="getDistributionNewsById",
     *     @OA\Response(response="200", description="success"),
     *     @OA\Parameter(
     *          name="id",
     *          description="News Id",
     *          required=true,
     *          in="path",
     *          example="11531",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *     ),
     * )
     */
    public function show(int $id)
    {
        return Cache::rememberForever('v2-news-' . $id, function () use ($id) {
            $crawler = $this->client->request('GET', (string) $this->baseUrl . "?newsid=$id");

            $tableElement = $crawler->filter('.News1 > table')->eq(1);
            $tdElement = $crawler->filter('.Background > td');

            return response()->json([
                'message' => 'success',
                'headline' => $this->newsService->getNewsHeadline($tableElement),
                'news_url' => $this->newsService->getDistrowatchNewsUrl($tableElement),
                'date' => $this->newsService->getNewsDate($tableElement),
                'thumbnail' => $this->newsService->getNewsThumbnail($tableElement),
                'about' => $this->newsService->getAboutText($tdElement),
                'body' => $this->newsService->getNewsBody($tableElement),
                'distribution' => [
                    'distrowatch' => $this->newsService->getDistrowatchDistributionUrl($tableElement),
                    'diwa' =>  $this->newsService->getDistributionDetailUrl(),
                ],
            ], Response::HTTP_OK);
        });
    }

    /**
     * @OA\Get(
     *     path="/api/v2/filter/news",
     *     tags={"News"},
     *     summary="Get specific distribution news",
     *     description="If one of the {params} not found, distrowatch.com will return the home page with default params(all). make sure all {params} are correct",
     *     operationId="FilterDistributionNews",
     *     @OA\Response(response="200", description="success"),
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
     *          example="2022",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *     ),
     * )
     */
    public function filter(Request $request)
    {
        $distribution = $request->distribution ?? 'all';
        $release = $request->release ?? 'all';
        $month = $request->month ?? 'all';
        $year = $request->year ?? 'all';

        $cacheName = Str::snake($distribution . ' ' . $release . ' ' . $month . ' ' . $year);

        $crawler = $this->client->request('GET', (string) $this->baseUrl . "?distribution=$distribution&release=$release&month=$month&year=$year");

        return Cache::remember($cacheName, now()->addDay(3), function () use ($crawler) {
            $news = $this->newsService->getNews($crawler->filter('.News1 > table'));

            return response()->json([
                'message' => 'success',
                'news' => $news
            ], Response::HTTP_OK);
        });
    }
}
