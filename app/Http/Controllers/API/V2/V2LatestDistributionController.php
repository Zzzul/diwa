<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Services\GoutteClientService;
use App\Services\LatestDistributionService;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class V2LatestDistributionController extends Controller
{
    /**
     * @var LatestDistributionService $latestDistributionService
     */
    public LatestDistributionService $latestDistributionService;

    /**
     * @var client
     */
    public $client;

    /**
     * @var baseUrl
     */
    public string $baseUrl;

    public function __construct(LatestDistributionService $latestDistributionService, GoutteClientService $goutteClientService)
    {
        $this->latestdistributionService = $latestDistributionService;
        $this->client = $goutteClientService->setup();
        $this->baseUrl = config('app.distrowatch_url');
    }

    /**
     * @OA\Get(
     *     path="/api/v2/latest/distributions",
     *     tags={"Latest Released"},
     *     summary="Get latest distributions",
     *     operationId="getLatestDistributions",
     *     @OA\Response(response="200", description="success")
     * )
     */
    public function __invoke()
    {
        return Cache::remember('v2-latest-distirbutions', now()->addDays(2), function () {
            $crawler = $this->client->request('GET', (string) $this->baseUrl);

            return response()->json([
                'message' => 'success',
                'latest_distibutions' => $this->latestdistributionService->get($crawler->filter('table.News')->eq(0)),
            ], Response::HTTP_OK);
        });
    }
}
