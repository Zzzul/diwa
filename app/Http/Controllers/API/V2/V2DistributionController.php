<?php

namespace App\Http\Controllers\API\V2;

use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Services\DistributionService;
use App\Services\GoutteClientService;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class V2DistributionController extends Controller
{
    /**
     * @var DistributionService distributionService
     */
    public DistributionService $distributionService;

    /**
     * @var client
     */
    public $client;

    /**
     * @var baseUrl
     */
    public string $baseUrl;

    public function __construct(DistributionService $distributionService, GoutteClientService $goutteClientService)
    {
        $this->distributionService = $distributionService;
        $this->client = $goutteClientService->setup();
        $this->baseUrl = config('app.distrowatch_url');
    }

    /**
     * @OA\Get(
     *     path="/api/v2/distributions",
     *     tags={"Distributions"},
     *     summary="Get all Distribution",
     *     operationId="getAllDistribution",
     *     @OA\Response(response="200", description="success")
     * )
     *
     *  @OA\Tag(
     *     name="Distributions",
     *     description="API Endpoints of Distribution"
     * )
     */
    public function index()
    {
        return Cache::remember('v2-distributions', now()->addWeek(), function () {
            $crawler = $this->client->request('GET', (string) $this->baseUrl);

            return response()->json([
                'message' => 'success',
                'distibutions' => $this->distributionService->getAllDistribution(
                    node: $crawler->filter('select')->children(),
                    baseUrl: (string) $this->baseUrl
                ),
            ], Response::HTTP_OK);
        });
    }

    /**
     * @OA\Get(
     *     path="/api/v2/distributions/{name}",
     *     tags={"Distributions"},
     *     summary="Get distribution information detail",
     *     description="If {name} not found, will return 404",
     *     operationId="findDistributionById",
     *     @OA\Response(response="200", description="success"),
     *     @OA\Parameter(
     *          name="name",
     *          description="Distribution Name",
     *          required=true,
     *          in="path",
     *          example="ubuntu",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     )
     * )
     */
    public function show(string $slug)
    {
        $cacheName = Str::snake('v2-distributions-' . $slug);

        return Cache::remember($cacheName, now()->addDay(3), function () use ($slug) {
            $crawler = $this->client->request('GET', (string) $this->baseUrl . "table.php?distribution=$slug");

            /**
             * Check for not found
             */
            $node = $crawler->filter('h1')->eq(0);

            if (count($node) == 0) {
                return response()->json([
                    'message' => 'distribution not found.',
                    'home' => route("v2.home")
                ], Response::HTTP_NOT_FOUND);
            }

            $filterUl = $crawler->filter('ul');

            $filterBackground = $crawler->filter('.Background');

            return response()->json([
                'message' => 'success',
                'distribution' => $this->distributionService->getDistributionName($node),
                'last_update' => $this->distributionService->getLastUpdate($node),
                'os_type' => $this->distributionService->getOsType($filterUl),
                'origin' => $this->distributionService->getOrigin($filterUl),
                'about' =>  $this->distributionService->getAboutText($crawler),
                'average_rating' => $this->distributionService->checkScoreAndAverageRating($crawler),
                'status' => $this->distributionService->getStatus($filterUl),
                'popularity' => $this->distributionService->getPopularity($filterUl),
                'homepage' => $this->distributionService->getHomepageUrl($filterBackground),
                'user_forum' => $this->distributionService->getUserForumUrl($filterBackground),
                'based_ons' => $this->distributionService->getBasedOns($filterUl),
                'architectures' => $this->distributionService->getArchitectures($filterUl),
                'desktop_environments' => $this->distributionService->getDesktopTypes($filterUl),
                'categories' => $this->distributionService->getCategories($filterUl),
                'mailing_list' => $this->distributionService->getMailingList($filterBackground),
                'screencasts' => $this->distributionService->getScreencasts($filterBackground),
                'where_to_buy_or_tries' => $this->distributionService->checkWhereToBuy($filterBackground),
                'related_websites' => $this->distributionService->getRelatedWebsites($filterBackground),
                'bug_tracker' => $this->distributionService->getBugTrackerLinks($filterBackground),
                'documentations' => $this->distributionService->getDocumentation($filterBackground),
                'screenshots' => $this->distributionService->getScreenshots($filterBackground),
                'download_mirrors' => $this->distributionService->getDownloadMirrorLinks($filterBackground),
                'reviews' => $this->distributionService->getReviews($filterBackground),
                'recent_related_news_and_releases' => $this->distributionService->recentRelatedNewsAndRealeses($filterBackground),
            ], Response::HTTP_OK);
        });
    }
}
