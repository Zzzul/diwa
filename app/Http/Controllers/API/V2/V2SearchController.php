<?php

namespace App\Http\Controllers\API\V2;

use Goutte\Client;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\{GoutteClientService, SearchService};
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class V2SearchController extends Controller
{
    /**
     * @var client
     */
    public $client;

    /**
     * @var SearchService searchService
     */
    public SearchService $searchService;

    /**
     * @var baseUrl
     */
    public string $baseUrl;

    public function __construct(GoutteClientService $goutteClientService, SearchService $searchService)
    {
        $this->client = $goutteClientService->setup();
        $this->searchService = $searchService;
        $this->baseUrl = config('app.distrowatch_url');
    }

    /**
     * @OA\Get(
     *     path="/api/v2/params/search",
     *     tags={"Distributions"},
     *     summary="Get all available parameters for search the distribution (below ↓)",
     *     operationId="GetAllAvailableParametersForSearch",
     *     @OA\Response(response="200", description="success")
     * )
     */
    public function index()
    {
        return Cache::rememberForever('v2-search',  function () {
            $crawler = $this->client->request('GET', (string) $this->baseUrl . 'search.php');

            $selectElement = $crawler->filter('.NewsText')->filter('table')->filter('select');

            return response()->json([
                'message' => 'success',
                'params' => $this->searchService->getAll($selectElement),
            ], Response::HTTP_OK);
        });
    }

    /**
     * @OA\Get(
     *     path="/api/v2/search",
     *     tags={"Distributions"},
     *     summary="Get specific distribution",
     *     description="If one of the {params} not found/empty, distrowatch.com will used default params(All)",
     *     operationId="FilterDistribution",
     *     @OA\Response(response="200", description="success"),
     *     @OA\Parameter(
     *          name="ostype",
     *          description="OS Type",
     *          required=true,
     *          in="query",
     *          example="Linux",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="category",
     *          description="Distribution Category",
     *          required=true,
     *          in="query",
     *          example="All",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="origin",
     *          description="Country of Origin",
     *          required=true,
     *          in="query",
     *          example="All",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="basedon",
     *          description="Based on",
     *          required=true,
     *          in="query",
     *          example="Ubuntu",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="notbasedon",
     *          description="Not Based on",
     *          required=true,
     *          in="query",
     *          example="None",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="desktop",
     *          description="Desktop Interface",
     *          required=true,
     *          in="query",
     *          example="Xfce",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="architecture",
     *          description="Architecture",
     *          required=true,
     *          in="query",
     *          example="All",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="package",
     *          description="Package Management",
     *          required=true,
     *          in="query",
     *          example="All",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="rolling",
     *          description="Release Model",
     *          required=true,
     *          in="query",
     *          example="All",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *      @OA\Parameter(
     *          name="isosize",
     *          description="Install Media Size",
     *          required=true,
     *          in="query",
     *          example="All",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *      @OA\Parameter(
     *          name="netinstall",
     *          description="Install Method",
     *          required=true,
     *          in="query",
     *          example="All",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *      @OA\Parameter(
     *          name="language",
     *          description="Multi Language Support",
     *          required=true,
     *          in="query",
     *          example="All",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *      @OA\Parameter(
     *          name="defaultinit",
     *          description="Init Sofrware",
     *          required=true,
     *          in="query",
     *          example="All",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *      @OA\Parameter(
     *          name="status",
     *          description="Status",
     *          required=true,
     *          in="query",
     *          example="Active",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     * )
     */
    public function show(Request $request)
    {
        $osType = $request->ostype ?? 'All';
        $category = $request->category ?? 'All';
        $origin = $request->origin ?? 'All';
        $basedOn = $request->basedon ?? 'All';
        $notBasedOn = $request->notbasedon ?? 'All';
        $desktop = $request->desktop ?? 'All';
        $architecture = $request->architecture ?? 'All';
        $package = $request->package ?? 'All';
        $rolling = $request->rolling ?? 'All';
        $isoSize = $request->isosize ?? 'All';
        $netInstall = $request->netinstall ?? 'All';
        $language = $request->language ?? 'All';
        $defaultInit = $request->defaultinit ?? 'All';
        $status = $request->status ?? 'Active';

        $cacheName = Str::camel(
            'v2-search ' .
                $osType . ' ' .
                $category . ' ' .
                $origin . ' ' .
                $basedOn . ' ' .
                $notBasedOn . ' ' .
                $desktop . ' ' .
                $architecture . ' ' .
                $package . ' ' .
                $rolling . ' ' .
                $isoSize . ' ' .
                $netInstall . ' ' .
                $language . ' ' .
                $defaultInit . ' ' .
                $status
        );

        $fullUrl = $this->baseUrl . "search.php?ostype=$osType&category=$category&origin=$origin&basedon=$basedOn&notbasedon=$notBasedOn&desktop=$desktop&architecture=$architecture&package=$package&rolling=$rolling&isosize=$isoSize&netinstall=$netInstall&language=$language&defaultinit=$defaultInit&status=$status#simple";

        return Cache::remember($cacheName, now()->addDay(3),  function () use ($fullUrl) {
            $crawler = $this->client->request('GET', $fullUrl);

            $results = $this->searchService->search($crawler->filter('.NewsText')->eq(1)->filter('b'));

            return response()->json([
                'message' => 'success',
                'results' => $results,
            ], Response::HTTP_OK);
        });
    }
}
