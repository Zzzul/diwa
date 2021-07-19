<?php

namespace App\Http\Controllers\API;

use Goutte\Client;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

class WeeklyNewsController extends Controller
{
    private $list = [];
    private $content = [];
    private $title = '';
    private $story = '';

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

        $crawler->filter('.List')->each(function ($node) {
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
        $client = new Client();

        $url = env('DISTROWATCH_URL') . "weekly.php?issue=$id";

        $crawler = $client->request('GET', $url);

        // title
        $this->title = $crawler->filter('.rTitle')->text();

        // stoty
        $remove_ul_text = Str::remove($crawler->filter('.rStory')->filter('ul')->text(), $crawler->filter('.rStory')->text());

        $this->story = Str::before($remove_ul_text, 'Content:  Listen to the Podcast');

        $crawler->filter('.rStory')->eq(0)->filter('ul')->filter('a')->each(function ($node) {
            $this->content[] = [
                'url' =>  $node->link()->getUri(),
                'text' => $node->text()
            ];
        });

        return response()->json([
            'message' => 'Success',
            'status_code' => Response::HTTP_OK,
            'issue' => [
                'title' => $this->title,
                'story' => $this->story,
                'content' => $this->content
            ]
        ], Response::HTTP_OK);
    }
}
