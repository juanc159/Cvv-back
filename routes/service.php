<?php

use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Service
|--------------------------------------------------------------------------
*/

Route::post('/service-list', [ServiceController::class, 'list'])->name('service.list');

Route::delete('/service-delete/{id}', [ServiceController::class, 'delete'])->name('service.delete');

Route::post('/service-changeState', [ServiceController::class, 'changeState'])->name('api.service.changeState');

Route::get('/service-dataForm/{action}/{id?}', [ServiceController::class, 'dataForm'])->name('api.permission.dataForm');

Route::post('/service-create', [ServiceController::class, 'store'])->name('service.store');

Route::put('/service-update', [ServiceController::class, 'store'])->name('service.update');
