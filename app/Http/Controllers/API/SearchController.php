<?php

namespace App\Http\Controllers\API;

use Goutte\Client;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchController extends Controller
{
    private $params = [];
    private $os_type = [];
    private $distribution_category = [];
    private $country_of_origin = [];
    private $based_on = [];
    private $not_based_on = [];
    private $desktop_interface = [];
    private $architecture = [];
    private $package_management = [];
    private $release_model = [];
    private $install_media_size = [];
    private $install_method = [];
    private $multi_language_support = [];
    private $init_software = [];
    private $status = [];

    public function index()
    {
        $client = new Client();

        $url = env('DISTROWATCH_URL') . 'search.php';

        $crawler = $client->request('GET', $url);

        $select = $crawler->filter('.NewsText')->filter('table')->filter('select');

        $select->eq(0)->children()->each(function ($node) {
            $this->params['os_type'][] = $node->attr('value');
        });

        $select->eq(1)->children()->each(function ($node) {
            $this->params['distribution_category'][] = $node->attr('value');
        });

        $select->eq(2)->children()->each(function ($node) {
            $this->params['country_of_origin'][] = $node->attr('value');
        });

        $select->eq(3)->children()->each(function ($node) {
            $this->params['based_on'][] = $node->attr('value');
        });

        $select->eq(4)->children()->each(function ($node) {
            $this->params['not_based_on'][] = $node->attr('value');
        });

        $select->eq(5)->children()->each(function ($node) {
            $this->params['desktop_environment'][] = $node->attr('value');
        });

        $select->eq(6)->children()->each(function ($node) {
            $this->params['architecture'][] = $node->attr('value');
        });

        $select->eq(7)->children()->each(function ($node) {
            $this->params['package_management'][] = $node->attr('value');
        });

        $select->eq(8)->children()->each(function ($node) {
            $this->params['release_model'][] = $node->attr('value');
        });

        $select->eq(9)->children()->each(function ($node) {
            $this->params['install_media_size'][] = $node->attr('value');
        });

        $select->eq(10)->children()->each(function ($node) {
            $this->params['install_method'][] = $node->attr('value');
        });

        $select->eq(11)->children()->each(function ($node) {
            $this->params['multi_language_support'][] = $node->attr('value');
        });

        $select->eq(12)->children()->each(function ($node) {
            $this->params['init_software'][] = $node->attr('value');
        });

        $select->eq(13)->children()->each(function ($node) {
            $this->params['status'][] = $node->attr('value');
        });

        return response()->json([
            'message' => 'Success',
            'status_code' => Response::HTTP_OK,
            'params' => $this->params,
        ], Response::HTTP_OK);
    }

    public function show(Request $request)
    {
        dd($request->os_type);
    }
}
