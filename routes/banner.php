<?php

use App\Http\Controllers\BannerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Banner
|--------------------------------------------------------------------------
*/

Route::post('/banner-list', [BannerController::class, 'list'])->name('banner.list');

Route::delete('/banner-delete/{id}', [BannerController::class, 'delete'])->name('banner.delete');

Route::post('/banner-changeState', [BannerController::class, 'changeState'])->name('api.banner.changeState');

Route::get('/banner-dataForm/{action}/{id?}', [BannerController::class, 'dataForm'])->name('api.permission.dataForm');

Route::post('/banner-create', [BannerController::class, 'store'])->name('banner.store');

Route::put('/banner-update', [BannerController::class, 'store'])->name('banner.update');
