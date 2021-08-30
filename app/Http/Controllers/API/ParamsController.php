<?php

namespace App\Http\Controllers\API;

use Goutte\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ParamsController extends Controller
{
    private $distribution = [];
    private $release = [];
    private $year = [];
    private $month = [];

    /**
     * @OA\Get(
     *     path="/api/params/ranking",
     *     tags={"Ranking"},
     *     summary="Get all available parameters for filter the ranking (below ↓)",
     *     operationId="GetAllAvailableParametersRanking",
     *     @OA\Response(response="200", description="Success")
     * )
     */
    public function rankingParams()
    {
        return Cache::rememberForever('rankingParams',  function () {
            $client = new Client();

            $url = config('app.distrowatch_url');

            $crawler = $client->request('GET', $url);

            // All distribution
            $crawler->filter('select')->eq(5)->children()->each(function ($node) {
                if ($node->attr('value') != null && $node->text() != '') {
                    $this->distribution[] = [
                        'slug' => $node->attr('value'),
                        'text' => $node->text(),
                    ];
                }
            });

            return response()->json([
                'message' => 'Success.',
                'status_code' => Response::HTTP_OK,
                'params' => $this->distribution
            ], Response::HTTP_OK);
        });
    }

    /**
     * @OA\Get(
     *     path="/api/params/news",
     *     tags={"News"},
     *     summary="Get all available parameters for filter the news (above ↑)",
     *     operationId="GetAllAvailableParametersNews",
     *     @OA\Response(response="200", description="Success")
     * )
     */
    public function newsParams()
    {
        return Cache::rememberForever('newsParams',  function () {
            $client = new Client();

            $url = config('app.distrowatch_url');

            $crawler = $client->request('GET', $url);

            // All distribution
            $crawler->filter('.Introduction')->filter('select')->eq(0)->filter('option')->each(function ($node) {
                $this->distribution[] = [
                    'slug' => $node->attr('value'),
                    'text' => $node->text(),
                ];
            });

            // Year
            $crawler->filter('.Introduction')->filter('select')->eq(1)->filter('option')->each(function ($node) {
                $this->release[] = [
                    'slug' => $node->attr('value'),
                    'text' => $node->text(),
                ];
            });

            // Months
            $crawler->filter('.Introduction')->filter('select')->eq(2)->filter('option')->each(function ($node) {
                $this->month[] = [
                    'slug' => $node->attr('value'),
                    'text' => $node->text(),
                ];
            });

            // Years
            $crawler->filter('.Introduction')->filter('select')->eq(3)->filter('option')->each(function ($node) {
                $this->year[] = [
                    'slug' => $node->attr('value'),
                    'text' => $node->text(),
                ];
            });

            return response()->json([
                'message' => 'Success.',
                'status_code' => Response::HTTP_OK,
                'params' => [
                    'distribution' => $this->distribution,
                    'release' => $this->release,
                    'month' => $this->month,
                    'year' => $this->year,
                ]
            ], Response::HTTP_OK);
        });
    }
}
