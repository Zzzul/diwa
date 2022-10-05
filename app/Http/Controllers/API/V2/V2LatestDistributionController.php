<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\GoutteClientService;
use App\Services\LatestDistributionService;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

class V2LatestDistributionController extends Controller
{
    /**
     * @var LatestDistributionService $latestdistributionService
     */
    public LatestDistributionService $latestdistributionService;

    /**
     * @var client
     */
    public $client;

    /**
     * @var baseUrl
     */
    public string $baseUrl;

    public function __construct(LatestDistributionService $latestdistributionService, GoutteClientService $goutteClientService)
    {
        $this->latestdistributionService = $latestdistributionService;
        $this->client = $goutteClientService->setup();
        $this->baseUrl = config('app.distrowatch_url');
    }

    public function index(Request $request)
    {
        return Cache::remember('v2-latest-distirbutions', now()->addDays(2), function () {
            $crawler = $this->client->request('GET', (string) $this->baseUrl);

            return response()->json([
                'message' => 'success',
                'latest_distibutions' => $this->latestdistributionService->getLatestDistributions($crawler->filter('table.News')->eq(0)),
            ], Response::HTTP_OK);
        });
    }
}
