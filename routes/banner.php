<?php

use App\Http\Controllers\BannerController;
use Illuminate\Support\Facades\Route;

// Rutas protegidas
Route::middleware(['check.permission:banner.list'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Banner
    |--------------------------------------------------------------------------
    */

    Route::get('/banner/list', [BannerController::class, 'list']);

    Route::get('/banner/create', [BannerController::class, 'create']);

    Route::post('/banner/store', [BannerController::class, 'store']);

    Route::get('/banner/{id}/edit', [BannerController::class, 'edit']);

    Route::post('/banner/update/{id}', [BannerController::class, 'update']);

    Route::delete('/banner/delete/{id}', [BannerController::class, 'delete']);

    Route::post('/banner/changeStatus', [BannerController::class, 'changeStatus']);
});
