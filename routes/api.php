<?php

use App\Http\Controllers\API\HomeController;
use App\Http\Controllers\API\NewsController;
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

Route::get('/rankings', RankingController::class)->name('rankings');

Route::get('/news', [NewsController::class, 'index'])->name('news');

Route::get('/news/{id}', [NewsController::class, 'show'])->name('news.show');
