<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Services\{GoutteClientService, LatestPodcastService};
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class V2LatestPodcastController extends Controller
{
    /**
     * @var LatestPodcastService $latestPodcastService
     */
    public LatestPodcastService $latestPodcastService;

    /**
     * @var client
     */
    public $client;

    /**
     * @var baseUrl
     */
    public string $baseUrl;

    public function __construct(GoutteClientService $goutteClientService, LatestPodcastService $latestPodcastService)
    {
        $this->client = $goutteClientService->setup();
        $this->baseUrl = config('app.distrowatch_url');
        $this->latestPodcastService = $latestPodcastService;
    }

    /**
     * @OA\Get(
     *     path="/api/v2/latest/podcasts",
     *     tags={"Latest Released"},
     *     summary="Get latest podcasts",
     *     operationId="getLatestpodcasts",
     *     @OA\Response(response="200", description="success")
     * )
     */
    public function __invoke()
    {
        return Cache::remember('v2-latest-podcasts', now()->addDays(2), function () {
            $crawler = $this->client->request('GET', (string) $this->baseUrl);

            return response()->json([
                'message' => 'success',
                'latest_podcasts' => $this->latestPodcastService->get($crawler->filter('table.News')->eq(7)),
            ], Response::HTTP_OK);
        });
    }
}
