<?php

namespace App\Http\Controllers\API\V1;

use Carbon\Carbon;
use Goutte\Client;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Services\GoutteClientService;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RankingController extends Controller
{
    protected $rankings = [];
    protected $distrowatch_distribution_detail_url = '';
    protected $distribution_detail_url = '';
    protected $alt = '';
    protected $image = '';
    protected $status = '';
    protected $data_span = '';

    /**
     * @var client
     */
    protected $client;

    /**
     * @var baseUrl
     */
    protected string $baseUrl;

    public function __construct(GoutteClientService $goutteClientService)
    {
        $this->client = $goutteClientService->setup();
        $this->baseUrl = config('app.distrowatch_url');
    }

    /**
     * @OA\Get(
     *     path="/api/ranking",
     *     tags={"Ranking"},
     *     summary="Get top 100 distribution ranking of last 6 months",
     *     operationId="GetTop100Rangking",
     *     @OA\Response(response="200", description="Success")
     * )
     *
     *  @OA\Tag(
     *     name="Ranking",
     *     description="API Endpoints of Ranking"
     * )
     */
    public function index()
    {
        return Cache::remember('rangkingDefault', 86400, function () {
            $crawler = $this->client->request('GET', (string) $this->baseUrl);

            $crawler->filter('.phr2')->each(function ($node, $i) {

                $this->distrowatch_distribution_detail_url =  $node->filter('a')->link()->getUri();

                $this->distribution_detail_url = route("distribution.show", Str::remove('https://distrowatch.com/', $this->distrowatch_distribution_detail_url));

                $hpd = $node->nextAll()->filter('img');
                if (count($hpd) > 0) {
                    $this->alt =  $hpd->attr('alt');
                    $this->status = ($this->alt == '<')
                        ? 'adown' : (($this->alt == '>')
                            ? 'aup' : 'alevel');
                    $this->image = $this->baseUrl . $hpd->attr('src');
                }

                $this->rankings[] = [
                    'no' => $i + 1,
                    'distribution' => $node->filter('a')->text(),
                    'distrowatch_distribution_detail_url' => $this->distrowatch_distribution_detail_url,
                    'distribution_detail_url' => $this->distribution_detail_url,
                    // hits per day
                    'hpd' => [
                        'count' => intval($node->nextAll()->text()),
                        'status' => $this->status,
                        'alt' => $this->alt,
                        'image' => $this->image,
                    ],
                    'hits_yesterday_count' => intval(Str::remove('Yesterday: ', $node->nextAll()->attr('title'))),
                ];
            });

            return response()->json([
                'message' => 'Success',
                'hpd' => 'Hits Per Day',
                'data_span' => 'Last 6 months',
                'rankings' => $this->rankings
            ], Response::HTTP_OK);
        });
    }

    /**
     * @OA\Get(
     *     path="/api/ranking/{slug}",
     *     tags={"Ranking"},
     *     summary="Get top 100 distribution ranking but with parameter",
     *     description="If {slug} not found, distrowatch.com will return the home page with default ranking(last 6 months). make sure {slug} is correct",
     *     operationId="getRankingnByParams",
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Parameter(
     *          name="slug",
     *          description="Distribution Slug",
     *          required=true,
     *          example="trending-1",
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     * )
     */
    public function show($slug)
    {
        $cache_name = Str::camel('ranking ' . $slug);

        return Cache::remember($cache_name, 86400, function () use ($slug) {
            $crawler = $this->client->request('GET', (string) $this->baseUrl . "?dataspan=$slug");

            $crawler->filter('select')->eq(5)->children()->each(function ($node) use ($slug) {
                if ($node->attr('value') == $slug) {
                    $this->data_span = $node->text();
                }
            });

            // dd($this->data_span);
            $this->data_span != '' ? $this->data_span = $this->data_span : $this->data_span = 'Last 6 months';

            $crawler->filter('.phr2')->each(function ($node, $i) {

                $this->distrowatch_distribution_detail_url =  $node->filter('a')->link()->getUri();

                $this->distribution_detail_url = route("distribution.show", Str::remove('https://distrowatch.com/', $this->distrowatch_distribution_detail_url));

                $hpd = $node->nextAll()->filter('img');
                if (count($hpd) > 0) {
                    $this->alt = $hpd->attr('alt');
                    $this->status = ($this->alt == '<')
                        ? 'adown' : (($this->alt == '>')
                            ? 'aup' : 'alevel');
                    $this->image = $this->baseUrl . $hpd->attr('src');
                }

                $this->alt = count($node->nextAll()->filter('img')) > 0 ? $node->nextAll()->filter('img')->attr('alt') : '';

                $this->rankings[] = [
                    'no' => $i + 1,
                    'distribution' => $node->filter('a')->text(),
                    'distrowatch_distribution_detail_url' => $this->distrowatch_distribution_detail_url,
                    'distribution_detail_url' => $this->distribution_detail_url,
                    // hits per day
                    'hpd' => [
                        'count' => intval($node->nextAll()->text()),
                        'status' => $this->status,
                        'alt' => $this->alt == null ? '' : $this->alt,
                        'image' => $this->image,
                    ],
                    'hits_yesterday_count' => intval(Str::remove('Yesterday: ', $node->nextAll()->attr('title'))),
                ];
            });

            return response()->json([
                'message' => 'Success',
                'hpd' => 'Hits Per Day',
                'data_span' => $this->data_span,
                'ranking' => $this->rankings,
            ], Response::HTTP_OK);
        });
    }
}
