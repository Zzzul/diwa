<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\V2\{DistributionController, HomeController};

Route::prefix('v2')->name('v2.')->group(function () {
    Route::get('/', HomeController::class)->name('home');

    // Route::apiResource('/news', DistributionNewsController::class)->only('index', 'show');
    // Route::get('filter/news', [DistributionNewsController::class, 'filterNews'])->name('news.filter');

    // Route::apiResource('/weekly', WeeklyNewsController::class)->only('index', 'show');

    // Route::apiResource('/ranking', RankingController::class)->only('index', 'show');

    Route::apiResource('/distributions', DistributionController::class)->only('index', 'show');

    // Route::get('/search', [SearchController::class, 'show'])->name('search.index');

    // Route::prefix('params')->group(function () {
    //     Route::get('/ranking', [ParamsController::class, 'rankingParams'])->name('params.ranking');

    //     Route::get('/news', [ParamsController::class, 'newsParams'])->name('params.news');

    //     Route::get('/search', [SearchController::class, 'index'])->name('params.search');
    // });
});
