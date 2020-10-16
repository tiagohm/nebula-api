<?php

use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\Api\DeepSkyController;

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

Route::prefix('dso')->group(function () {
    Route::get('/search', [DeepSkyController::class, 'search']);
    Route::get('/{id}', [DeepSkyController::class, 'get'])->where('id', '[0-9]+');
    Route::get('/{id}/photo', [DeepSkyController::class, 'photo'])->where('id', '[0-9]+');
});
