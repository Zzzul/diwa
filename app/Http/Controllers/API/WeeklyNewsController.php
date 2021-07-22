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
     * @OA\Get(
     *     path="/api/weekly",
     *     tags={"News"},
     *     summary="Get all weekly news",
     *     description="Warning!, big size response",
     *     operationId="getAllWeeklyNews",
     *     @OA\Response(response="200", description="Success")
     * )
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
     * @OA\Get(
     *     path="/api/weekly/{id}",
     *     tags={"News"},
     *     summary="Get weekly news information detail",
     *     description="If {weekly_id} not found, distrowatch.com will return the latest weekly news. make sure {weekly_id} is correct",
     *     operationId="getWeeklyNewsById",
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Parameter(
     *          name="id",
     *          description="Weekly News Id",
     *          required=true,
     *          in="path",
     *          example="20210719",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *     ),
     * )
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
