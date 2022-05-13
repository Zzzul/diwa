<?php

namespace App\Http\Controllers\API\V1;

use Goutte\Client;
use App\Http\Controllers\Controller;
use App\Services\GoutteClientService;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ParamsController extends Controller
{
    /**
     * @var client
     */
    protected $client;

    /**
     * @var baseUrl
     */
    protected string $baseUrl;

    /**
     * @var distribution
     */
    protected array $distribution = [];

    /**
     * @var release
     */
    protected array $release = [];

    /**
     * @var year
     */
    protected array $year = [];

    /**
     * @var month
     */
    protected array $month = [];

    public function __construct(GoutteClientService $goutteClientService)
    {
        $this->client = $goutteClientService->setup();
        $this->baseUrl = config('app.distrowatch_url');
    }

    public function rankingParams()
    {
        return Cache::rememberForever('rankingParams',  function () {
            $crawler = $this->client->request('GET', (string) $this->baseUrl);

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

    public function newsParams()
    {
        return Cache::rememberForever('newsParams',  function () {
            $crawler = $this->client->request('GET', (string) $this->baseUrl);

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
