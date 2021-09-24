<?php

namespace App\Http\Controllers\API;

use Goutte\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ParamsController extends Controller
{
    private array $distribution = [];
    private array $release = [];
    private array $year = [];
    private array $month = [];

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
                'message' => 'Success',
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

            $filter_select_element = $crawler->filter('.Introduction')->filter('select');

            return response()->json([
                'message' => 'Success',
                'params' => [
                    'distribution' => $this->getDistributions($filter_select_element),
                    'release' => $this->getRelease($filter_select_element),
                    'month' => $this->getMonths($filter_select_element),
                    'year' => $this->getYears($filter_select_element),
                ]
            ], Response::HTTP_OK);
        });
    }


    private function getDistributions($filter_select_element)
    {
        $filter_select_element->eq(0)->filter('option')->each(function ($node) {
            $this->distribution[] = [
                'slug' => $node->attr('value'),
                'text' => $node->text(),
            ];
        });

        return $this->distribution;
    }

    private function getRelease($filter_select_element)
    {
        $filter_select_element->eq(1)->filter('option')->each(function ($node) {
            $this->release[] = [
                'slug' => $node->attr('value'),
                'text' => $node->text(),
            ];
        });

        return $this->release;
    }

    private function getMonths($filter_select_element)
    {
        $filter_select_element->eq(2)->filter('option')->each(function ($node) {
            $this->month[] = [
                'slug' => $node->attr('value'),
                'text' => $node->text(),
            ];
        });

        return $this->month;
    }

    private function getYears($filter_select_element)
    {
        $filter_select_element->eq(3)->filter('option')->each(function ($node) {
            $this->year[] = [
                'slug' => $node->attr('value'),
                'text' => $node->text(),
            ];
        });

        return  $this->year;
    }
}
