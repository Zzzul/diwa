<?php

namespace App\Http\Controllers\API;

use Goutte\Client;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Services\DistributionService;
use App\Services\GoutteClientService;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class DistributionController extends Controller
{
    /**
     * @var distributionService
     */
    public $distributionService;

    /**
     * @var client
     */
    public $client;

    public function __construct(DistributionService $distributionService, GoutteClientService $goutteClientService)
    {
        $this->distributionService = $distributionService;
        $this->client = $goutteClientService->setup();
    }

    /**
     * @OA\Get(
     *     path="/api/distribution",
     *     tags={"Distribution"},
     *     summary="Get all Distribution",
     *     operationId="getAllDistribution",
     *     @OA\Response(response="200", description="Success")
     * )
     *
     *  @OA\Tag(
     *     name="Distribution",
     *     description="API Endpoints of Distribution"
     * )
     */
    public function index()
    {
        // return Cache::remember('distributions', now()->addWeek(), function () {

            $baseUrl = config('app.distrowatch_url');

            $crawler = $this->client->request('GET', $baseUrl);

            return response()->json([
                'message' => 'success',
                'distibutions' => $this->distributionService->getAllDistribution(node: $crawler->filter('select')->children(), baseUrl: $baseUrl),
            ], Response::HTTP_OK);
        // });
    }

    /**
     * @OA\Get(
     *     path="/api/distribution/{name}",
     *     tags={"Distribution"},
     *     summary="Get distribution information detail",
     *     description="If {name} not found, will return 404",
     *     operationId="getDistributionById",
     *     @OA\Response(response="200", description="Success"),
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
        $cacheName = Str::camel('distribution ' . $slug);

        // return Cache::remember($cacheName, now()->addDay(), function () use ($slug) {

            $baseUrl = config('app.distrowatch_url') . "table.php?distribution=$slug";

            $crawler = $this->client->request('GET', $baseUrl);

            // Check for not found
            $node = $crawler->filter('h1')->eq(0);

            if (count($node) == 0) {
                return response()->json([
                    'message' => 'distribution not found.',
                    'home' => route("home")
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
                'mailing_list' => $this->distributionService->getMailingList($filterBackground),
                'user_forum' => $this->distributionService->getUserForumUrl($filterBackground),
                'alternative_user_forum' => $this->distributionService->getAlternativeUserForum($filterBackground),
                'based_ons' => $this->distributionService->getBasedOns($filterUl),
                'architectures' => $this->distributionService->getArchitectures($filterUl),
                'desktops' => $this->distributionService->getDesktopTypes($filterUl),
                'categories' => $this->distributionService->getCategories($filterUl),
                'documentations' => $this->distributionService->getDocumentation($filterBackground),
                'screenshots' => $this->distributionService->getScreenshots($filterBackground),
                'screencasts' => $this->distributionService->getScreencasts($filterBackground),
                'download_mirrors' => $this->distributionService->getDownloadMirrorLinks($filterBackground),
                'bug_tracker' => $this->distributionService->getBugTrackerLinks($filterBackground),
                'related_websites' => $this->distributionService->getRelatedWebsites($filterBackground),
                'reviews' => $this->distributionService->getReviews($filterBackground),
                'where_to_buy_or_tries' => $this->distributionService->checkWhereToBuy($filterBackground),
                'recent_related_news_and_releases' => $this->distributionService->recentRelatedNewsAndRealeses($filterBackground),
            ], Response::HTTP_OK);
        // });
    }
}
