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

Route::get('/params/ranking', [ParamsController::class, 'rankingParams'])->name('params.ranking');
Route::get('/params/news', [ParamsController::class, 'newsParams'])->name('params.news');

Route::get('/ranking', [RankingController::class, 'index'])->name('ranking.index');
Route::get('/ranking/{slug}', [RankingController::class, 'show'])->name('ranking.show');

Route::get('/news', [NewsController::class, 'index'])->name('news');
Route::get('/news/{id}', [NewsController::class, 'show'])->name('news.show');

Route::get('/distribution', [DistributionController::class, 'index'])->name('distribution.index');
Route::get('/distribution/{slug}', [DistributionController::class, 'show'])->name('distribution.show');
