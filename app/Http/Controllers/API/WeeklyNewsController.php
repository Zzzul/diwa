<?php

namespace App\Http\Controllers\API;

use Goutte\Client;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

class WeeklyNewsController extends Controller
{
    private $list = [];
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $client = new Client();

        $url = env('DISTROWATCH_URL') . 'weekly.php';

        $crawler = $client->request('GET', $url);

        $crawler->filter('.List')->each(function ($node, $i) {
            $this->list[] = [
                'distrowatch_weekly_detail_url' => $node->filter('a')->link()->getUri(),
                'weekly_detail_url' => 'coming soon',
                'text' => Str::remove('• ', $node->text())
            ];
        });

        return response()->json([
            'message' => 'Success',
            'status_code' => Response::HTTP_OK,
            'list' => $this->list
        ], Response::HTTP_OK);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }
}
