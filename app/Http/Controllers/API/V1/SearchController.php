<?php

namespace App\Http\Controllers\API\V1;

use Goutte\Client;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\GoutteClientService;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class SearchController extends Controller
{
    protected array $params = [];
    protected array $lists = [];

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

    public function index()
    {
        return Cache::rememberForever('searchIndex',  function () {
            $crawler = $this->client->request('GET', (string) $this->baseUrl . 'search.php');

            $filter_select_element = $crawler->filter('.NewsText')->filter('table')->filter('select');

            return response()->json([
                'message' => 'Success',
                'params' => $this->getAllValueForParams($filter_select_element),
            ], Response::HTTP_OK);
        });
    }

    public function show(Request $request)
    {
        $os_type = $request->ostype ?? 'All';
        $category = $request->category ?? 'All';
        $origin = $request->origin ?? 'All';
        $based_on = $request->basedon ?? 'All';
        $not_based_on = $request->notbasedon ?? 'All';
        $desktop = $request->desktop ?? 'All';
        $architecture = $request->architecture ?? 'All';
        $package = $request->package ?? 'All';
        $rolling = $request->rolling ?? 'All';
        $iso_size = $request->isosize ?? 'All';
        $net_install = $request->netinstall ?? 'All';
        $language = $request->language ?? 'All';
        $default_init = $request->defaultinit ?? 'All';
        $status = $request->status ?? 'Active';

        // dynamic cache name
        $cache_name = Str::camel(
            $os_type . ' ' .
                $category . ' ' .
                $origin . ' ' .
                $based_on . ' ' .
                $not_based_on . ' ' .
                $desktop . ' ' .
                $architecture . ' ' .
                $package . ' ' .
                $rolling . ' ' .
                $iso_size . ' ' .
                $net_install . ' ' .
                $language . ' ' .
                $default_init . ' ' .
                $status
        );

        return Cache::rememberForever($cache_name,  function () use (
            $os_type,
            $category,
            $origin,
            $based_on,
            $not_based_on,
            $desktop,
            $architecture,
            $package,
            $rolling,
            $iso_size,
            $net_install,
            $language,
            $default_init,
            $status
        ) {
            $fullUrl = $this->baseUrl . "search.php?ostype=$os_type&category=$category&origin=$origin&basedon=$based_on&notbasedon=$not_based_on&desktop=$desktop&architecture=$architecture&package=$package&rolling=$rolling&isosize=$iso_size&netinstall=$net_install&language=$language&defaultinit=$default_init&status=$status#simple";

            $crawler = $this->client->request('GET', $fullUrl);

            $crawler->filter('.NewsText')->eq(1)->filter('b')->each(function ($node, $i) {
                /**
                 * $i on 14 = The following distributions match your criteria (sorted by <a href="dwres.php?resource=popularity">popularity</a>):
                 */

                if ($i >= 15) {

                    /**
                     * ex:
                     * before: '1. MX Linux (1)'_
                     * Str::after($node->text(), '(')
                     * after: '1)'
                     */
                    $ranking = Str::after($node->text(), '(');

                    $url = $node->filter('a')->link()->getUri();

                    $this->lists[] = [
                        'distribution' => $node->filter('a')->text(),
                        'distrowatch_distribution_detail_url' => $url,
                        // get string after https://distrowatch.com/
                        'distribution_detail_url' => route("distribution.show", Str::after($url, 'com/')),
                        // remove ')' to get ranking
                        'ranking' => Str::remove(')', $ranking)
                    ];
                }
            });

            return response()->json([
                'message' => 'Success',
                'lists' => $this->lists,
            ], Response::HTTP_OK);
        });
    }

    public function getAllValueForParams($filter_select_element)
    {
        $filter_select_element->eq(0)->children()->each(function ($node) {
            $this->params['os_type'][] = $node->attr('value');
        });

        $filter_select_element->eq(1)->children()->each(function ($node) {
            $this->params['distribution_category'][] = $node->attr('value');
        });

        $filter_select_element->eq(2)->children()->each(function ($node) {
            $this->params['country_of_origin'][] = $node->attr('value');
        });

        $filter_select_element->eq(3)->children()->each(function ($node) {
            $this->params['based_on'][] = $node->attr('value');
        });

        $filter_select_element->eq(4)->children()->each(function ($node) {
            $this->params['not_based_on'][] = $node->attr('value');
        });

        $filter_select_element->eq(5)->children()->each(function ($node) {
            $this->params['desktop_environment'][] = $node->attr('value');
        });

        $filter_select_element->eq(6)->children()->each(function ($node) {
            $this->params['architecture'][] = $node->attr('value');
        });

        $filter_select_element->eq(7)->children()->each(function ($node) {
            $this->params['package_management'][] = $node->attr('value');
        });

        $filter_select_element->eq(8)->children()->each(function ($node) {
            $this->params['release_model'][] = $node->attr('value');
        });

        $filter_select_element->eq(9)->children()->each(function ($node) {
            $this->params['install_media_size'][] = $node->attr('value');
        });

        $filter_select_element->eq(10)->children()->each(function ($node) {
            $this->params['install_method'][] = $node->attr('value');
        });

        $filter_select_element->eq(11)->children()->each(function ($node) {
            $this->params['multi_language_support'][] = $node->attr('value');
        });

        $filter_select_element->eq(12)->children()->each(function ($node) {
            $this->params['init_software'][] = $node->attr('value');
        });

        $filter_select_element->eq(13)->children()->each(function ($node) {
            $this->params['status'][] = $node->attr('value');
        });

        return $this->params;
    }
}
