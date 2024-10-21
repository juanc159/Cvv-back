<?php

use App\Http\Controllers\JobPositionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| JobPosition
|--------------------------------------------------------------------------
*/

Route::get('/jobPosition-list', [JobPositionController::class, 'list']);

Route::delete('/jobPosition-delete/{id}', [JobPositionController::class, 'delete']);

Route::post('/jobPosition-changeState', [JobPositionController::class, 'changeState']);

Route::get('/jobPosition-dataForm/{action}/{id?}', [JobPositionController::class, 'dataForm']);

Route::post('/jobPosition-create', [JobPositionController::class, 'store']);

Route::put('/jobPosition-update', [JobPositionController::class, 'store']);
