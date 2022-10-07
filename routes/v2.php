<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\V2\{
    V2DistributionController,
    v2NewsController,
    v2HomeController,
    V2LatestDistributionController,
    V2LatestHeadlineController,
    V2LatestPackageController,
    V2LatestReviewController,
    V2RankingController,
    V2ParamsController,
    V2SearchController,
    V2WeeklyNewsController
};

Route::prefix('v2')->name('v2.')->group(function () {
    Route::get('/', v2HomeController::class)->name('home');

    Route::apiResource('/distributions', V2DistributionController::class)->only('index', 'show');
    Route::apiResource('/rankings', V2RankingController::class)->only('index', 'show');

    Route::apiResource('/news', v2NewsController::class)->only('index', 'show');
    Route::get('filter/news', [v2NewsController::class, 'filter'])->name('news.filter');

    Route::apiResource('/weekly', V2WeeklyNewsController::class)->only('index', 'show');

    Route::get('/search', [V2SearchController::class, 'show'])->name('search.index');

    Route::prefix('latest')->group(function () {
        Route::get('/distributions', V2LatestDistributionController::class)->name('latest.distributions');
        Route::get('/packages', V2LatestPackageController::class)->name('latest.packages');
        Route::get('/headlines', V2LatestHeadlineController::class)->name('latest.headlines');
        Route::get('/reviews', V2LatestReviewController::class)->name('latest.reviews');
    });

    Route::prefix('params')->group(function () {
        Route::get('/rankings', [V2ParamsController::class, 'rankings'])->name('params.rankings');
        Route::get('/news', [V2ParamsController::class, 'news'])->name('params.news');
        Route::get('/search', [V2SearchController::class, 'index'])->name('params.search');
    });
});
