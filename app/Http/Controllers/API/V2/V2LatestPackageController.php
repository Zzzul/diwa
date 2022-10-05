<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Services\GoutteClientService;
use App\Services\LatestPackageService;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class V2LatestPackageController extends Controller
{
    /**
     * @var LatestPackageService $latestPackageService
     */
    public LatestPackageService $latestPackageService;

    /**
     * @var client
     */
    public $client;

    /**
     * @var baseUrl
     */
    public string $baseUrl;

    public function __construct(GoutteClientService $goutteClientService, LatestPackageService $latestPackageService)
    {
        $this->client = $goutteClientService->setup();
        $this->baseUrl = config('app.distrowatch_url');
        $this->latestPackageService = $latestPackageService;
    }

    public function __invoke()
    {
        return Cache::remember('v2-latest-packages', now()->addDays(2), function () {
            $crawler = $this->client->request('GET', (string) $this->baseUrl);

            return response()->json([
                'message' => 'success',
                'latest_packages' => $this->latestPackageService->get($crawler->filter('table.News')->eq(1)),
            ], Response::HTTP_OK);
        });
    }
}
