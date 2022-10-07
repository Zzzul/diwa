<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Services\GoutteClientService;
use App\Services\LatestNewsletterService;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class V2LatestNewsletterController extends Controller
{
    /**
     * @var LatestNewsletterService $latestNewsletterService
     */
    public LatestNewsletterService $latestNewsletterService;

    /**
     * @var client
     */
    public $client;

    /**
     * @var baseUrl
     */
    public string $baseUrl;

    public function __construct(GoutteClientService $goutteClientService, LatestNewsletterService $latestNewsletterService)
    {
        $this->client = $goutteClientService->setup();
        $this->baseUrl = config('app.distrowatch_url');
        $this->latestNewsletterService = $latestNewsletterService;
    }

    /**
     * @OA\Get(
     *     path="/api/v2/latest/newsletters",
     *     tags={"Latest Released"},
     *     summary="Get latest newsletters",
     *     operationId="getLatestNewsletters",
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
        return Cache::remember('v2-latest-newsletters', now()->addDays(2), function () {
            $crawler = $this->client->request('GET', (string) $this->baseUrl);

            return response()->json([
                'message' => 'success',
                'latest_newsletters' => $this->latestNewsletterService->get($crawler->filter('table.News')->eq(6)),
            ], Response::HTTP_OK);
        });
    }
}
