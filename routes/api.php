<?php

use App\Http\Controllers\API\DistributionController;
use App\Http\Controllers\API\HomeController;
use App\Http\Controllers\API\NewsController;
use App\Http\Controllers\API\ParamsController;
use App\Http\Controllers\API\RankingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/', HomeController::class)->name('home');

Route::prefix('params')->group(function () {
    Route::get('/ranking', [ParamsController::class, 'rankingParams'])->name('params.ranking');
    Route::get('/news', [ParamsController::class, 'newsParams'])->name('params.news');
});

Route::get('/news/filter/distribution={distribution}&release={release}&month={month}&year={year}', [NewsController::class, 'filteringNews'])->name('news.filteringNews');

Route::resource('/news', NewsController::class)->only('index', 'show');

Route::resource('/ranking', RankingController::class)->only('index', 'show');

Route::resource('/distribution', DistributionController::class)->only('index', 'show');
