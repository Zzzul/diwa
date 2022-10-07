<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Services\GoutteClientService;
use App\Services\LatestHeadlineService;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class V2LatestHeadlineController extends Controller
{
        /**
     * @var LatestHeadlineService $latestHeadlineService
     */
    public LatestHeadlineService $latestHeadlineService;

    /**
     * @var client
     */
    public $client;

    /**
     * @var baseUrl
     */
    public string $baseUrl;

    public function __construct(GoutteClientService $goutteClientService, LatestHeadlineService $latestHeadlineService)
    {
        $this->client = $goutteClientService->setup();
        $this->baseUrl = config('app.distrowatch_url');
        $this->latestHeadlineService = $latestHeadlineService;
    }

    /**
     * @OA\Get(
     *     path="/api/v2/latest/headlines",
     *     tags={"Latest Released"},
     *     summary="Get latest headlines",
     *     operationId="getLatestheadlines",
     *     @OA\Response(response="200", description="success")
     * )
     */
    public function __invoke()
    {
        return Cache::remember('v2-latest-headlines', now()->addDay(), function () {
            $crawler = $this->client->request('GET', (string) $this->baseUrl);

            return response()->json([
                'message' => 'success',
                'latest_headlines' => $this->latestHeadlineService->get($crawler->filter('table.News')->eq(4)),
            ], Response::HTTP_OK);
        });
    }
}
