<?php

namespace App\Http\Controllers\API\V2;

use Goutte\Client;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Services\GoutteClientService;
use App\Services\RankingService;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class V2RankingController extends Controller
{
    /**
     * @var rankingService
     */
    protected RankingService $rankingService;

    /**
     * @var client
     */
    protected $client;

    /**
     * @var baseUrl
     */
    protected string $baseUrl;

    public function __construct(GoutteClientService $goutteClientService, RankingService $rankingService)
    {
        $this->client = $goutteClientService->setup();
        $this->baseUrl = config('app.distrowatch_url');
        $this->rankingService = $rankingService;
    }

    /**
     * @OA\Get(
     *     path="/api/v2/rankings",
     *     tags={"v2-Rankings"},
     *     summary="Get top 100 distributions rankings of last 6 months",
     *     operationId="Top100Rangking",
     *     @OA\Response(response="200", description="success")
     * )
     *
     *  @OA\Tag(
     *     name="Ranking",
     *     description="API Endpoints of Ranking"
     * )
     */
    public function index()
    {
        return Cache::remember('v2-rangkings', 86400, function () {
            $crawler = $this->client->request('GET', (string) $this->baseUrl);

            $crawler->filter('.phr2')->each(function ($node, $i) {

                $this->distrowatch_distribution_detail_url =  $node->filter('a')->link()->getUri();

                $this->distribution_detail_url = route("v2.distributions.show", Str::remove('https://distrowatch.com/', $this->distrowatch_distribution_detail_url));

                $hpd = $node->nextAll()->filter('img');
                if (count($hpd) > 0) {
                    $this->alt =  $hpd->attr('alt');
                    $this->status = ($this->alt == '<')
                        ? 'adown' : (($this->alt == '>')
                            ? 'aup' : 'alevel');
                }

                $this->rankings[] = [
                    'no' => $i + 1,
                    'distribution' => $node->filter('a')->text(),
                    'hits_per_day_count' => intval($node->nextAll()->text()),
                    'status' => $this->status,
                    'hits_yesterday_count' => intval(Str::remove('Yesterday: ', $node->nextAll()->attr('title'))),
                    'detail' => [
                        'distrowatch' => $this->distrowatch_distribution_detail_url,
                        'diwa' => $this->distribution_detail_url,
                    ],
                ];
            });

            $rankings = $this->rankingService->getAll($crawler->filter('.phr2'));

            return response()->json([
                'message' => 'success',
                'data_span' => 'last 6 months',
                'rankings' => $rankings
            ], Response::HTTP_OK);
        });
    }

    /**
     * @OA\Get(
     *     path="/api/v2/rankings/{slug}",
     *     tags={"v2-Rankings"},
     *     summary="Get top 100 distributions rankings with parameter",
     *     description="If {slug} not found, distrowatch.com will return the home page with default rankings(last 6 months). make sure {slug} is correct",
     *     operationId="findRankingnByParams",
     *     @OA\Response(response="200", description="success"),
     *     @OA\Parameter(
     *          name="slug",
     *          description="Distribution Slug",
     *          required=true,
     *          example="trending-1",
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     * )
     */
    public function show(string $slug)
    {
        $cache_name = Str::camel('v2-rankings-' . $slug);

        return Cache::remember($cache_name, 86400, function () use ($slug) {
            $crawler = $this->client->request('GET', (string) $this->baseUrl . "?dataspan=$slug");

            $dataSpan = $this->rankingService->getDataSpan(
                node: $crawler->filter('select')->eq(5)->children(),
                slug: $slug
            );

            $rankings = $this->rankingService->getAll($crawler->filter('.phr2'));

            return response()->json([
                'message' => 'success',
                'data_span' => $dataSpan,
                'ranking' => $rankings,
            ], Response::HTTP_OK);
        });
    }
}
