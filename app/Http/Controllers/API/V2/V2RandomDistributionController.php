<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Services\{GoutteClientService, RandomDistributionService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class V2RandomDistributionController extends Controller
{
        /**
     * @var RandomDistributionService $randomDistributionService
     */
    public RandomDistributionService $randomDistributionService;

    /**
     * @var client
     */
    public $client;

    /**
     * @var baseUrl
     */
    public string $baseUrl;

    public function __construct(GoutteClientService $goutteClientService, RandomDistributionService $randomDistributionService)
    {
        $this->client = $goutteClientService->setup();
        $this->baseUrl = config('app.distrowatch_url');
        $this->randomDistributionService = $randomDistributionService;
    }

    /**
     * @OA\Get(
     *     path="/api/v2/random",
     *     tags={"Distributions"},
     *     summary="Get random distribution (result will reset every 1 hour)",
     *     operationId="getRandomDistribution",
     *     @OA\Response(response="200", description="success")
     * )
     */
    public function __invoke()
    {
        return Cache::remember('v2-random-distribution', now()->addHour(), function () {
            $crawler = $this->client->request('GET', (string) $this->baseUrl);

            return response()->json([
                'message' => 'success',
                'distribution' => $this->randomDistributionService->get($crawler->filter('table.News')->eq(8)->nextAll()->nextAll()->filter('td')),
            ], Response::HTTP_OK);
        });
    }
}
