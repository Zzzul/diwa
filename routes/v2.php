<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\V2\{V2DistributionController, v2HomeController, V2RankingController,V2ParamsController, V2SearchController};

Route::prefix('v2')->name('v2.')->group(function () {
    Route::get('/', v2HomeController::class)->name('home');

    Route::apiResource('/distributions', V2DistributionController::class)->only('index', 'show');
    Route::apiResource('/rankings', V2RankingController::class)->only('index', 'show');

    // Route::apiResource('/news', DistributionNewsController::class)->only('index', 'show');
    // Route::get('filter/news', [DistributionNewsController::class, 'filterNews'])->name('news.filter');

    // Route::apiResource('/weekly', WeeklyNewsController::class)->only('index', 'show');

    Route::get('/search', [V2SearchController::class, 'show'])->name('search.index');

    Route::prefix('params')->group(function () {
        Route::get('/rankings', [V2ParamsController::class, 'rankings'])->name('params.rankings');
        Route::get('/news', [V2ParamsController::class, 'news'])->name('params.news');
        Route::get('/search', [V2SearchController::class, 'index'])->name('params.search');
    });
});
