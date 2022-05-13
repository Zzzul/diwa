<?php

namespace App\Http\Controllers\API\V2;

use Goutte\Client;
use App\Http\Controllers\Controller;
use App\Services\GoutteClientService;
use App\Services\ParamService;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class V2ParamsController extends Controller
{
    /**
     * @var client
     */
    public $client;

    /**
     * @var ParamService paramService
     */
    public ParamService $paramService;

    /**
     * @var baseUrl
     */
    public string $baseUrl;

    public function __construct(GoutteClientService $goutteClientService, ParamService $paramService)
    {
        $this->client = $goutteClientService->setup();
        $this->paramService = $paramService;
        $this->baseUrl = config('app.distrowatch_url');
    }

    /**
     * @OA\Get(
     *     path="/api/v2/params/rankings",
     *     tags={"Rankings"},
     *     summary="Get all available parameters for filter the rankings (below ↓)",
     *     operationId="GetAllAvailableParametersRanking",
     *     @OA\Response(response="200", description="success")
     * )
     */
    public function rankings()
    {
        return Cache::remember('v2-params-rangkings', now()->addYear(),  function () {
            $crawler = $this->client->request('GET', (string) $this->baseUrl);

            $params = $this->paramService->getRankings($crawler->filter('select')->eq(5)->children());

            return response()->json([
                'message' => 'success',
                'params' => $params
            ], Response::HTTP_OK);
        });
    }

    /**
     * @OA\Get(
     *     path="/api/v2/params/news",
     *     tags={"News"},
     *     summary="Get all available parameters for filter the news (below ↓)",
     *     operationId="GetAllAvailableParametersNews",
     *     @OA\Response(response="200", description="success")
     * )
     */
    public function news()
    {
        return Cache::remember('v2-params-news', now()->addYear(),  function () {
            $crawler = $this->client->request('GET', (string) $this->baseUrl);

            $selectElement = $crawler->filter('.Introduction')->filter('select');

            return response()->json([
                'message' => 'success',
                'params' => [
                    'distributions' => $this->paramService->getDistributions($selectElement),
                    'releases' => $this->paramService->getReleases($selectElement),
                    'months' => $this->paramService->getMonths($selectElement),
                    'years' => $this->paramService->getYears($selectElement),
                ]
            ], Response::HTTP_OK);
        });
    }
}
