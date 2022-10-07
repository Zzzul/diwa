<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Services\GoutteClientService;
use App\Services\LatestReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class V2LatestReviewController extends Controller
{
    /**
     * @var LatestReviewService $latestReviewService
     */
    public LatestReviewService $latestReviewService;

    /**
     * @var client
     */
    public $client;

    /**
     * @var baseUrl
     */
    public string $baseUrl;

    public function __construct(GoutteClientService $goutteClientService, LatestReviewService $latestReviewService)
    {
        $this->client = $goutteClientService->setup();
        $this->baseUrl = config('app.distrowatch_url');
        $this->latestReviewService = $latestReviewService;
    }

    /**
     * @OA\Get(
     *     path="/api/v2/latest/reviews",
     *     tags={"Latest Released"},
     *     summary="Get latest reviews",
     *     operationId="getLatestReviews",
     *     @OA\Response(response="200", description="success")
     * )
     *
     *  @OA\Tag(
     *     name="Latest Released",
     *     description="API Endpoints of latest released"
     * )
     */
    public function __invoke()
    {
        return Cache::remember('v2-latest-reviews', now()->addDay(), function () {
            $crawler = $this->client->request('GET', (string) $this->baseUrl);

            return response()->json([
                'message' => 'success',
                'latest_reviews' => $this->latestReviewService->get($crawler->filter('table.News')->eq(5)),
            ], Response::HTTP_OK);
        });
    }
}
