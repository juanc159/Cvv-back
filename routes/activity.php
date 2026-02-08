<?php

use App\Http\Controllers\ActivityController;
use Illuminate\Support\Facades\Route;

// Rutas protegidas

/*
|--------------------------------------------------------------------------
| Activity
|--------------------------------------------------------------------------
*/

Route::get('/activity/list', [ActivityController::class, 'list']);

Route::get('/activity/create', [ActivityController::class, 'create']);

Route::post('/activity/store', [ActivityController::class, 'store']);

Route::get('/activity/{id}/edit', [ActivityController::class, 'edit']);

Route::post('/activity/update/{id}', [ActivityController::class, 'update']);

Route::delete('/activity/delete/{id}', [ActivityController::class, 'delete']);

Route::post('/activity/changeStatus', [ActivityController::class, 'changeStatus']);
