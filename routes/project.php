<?php

use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Project
|--------------------------------------------------------------------------
*/

Route::get('/project/list', [ProjectController::class, 'list']);

Route::post('/project/store', [ProjectController::class, 'store']);

Route::get('/project/{id}/edit', [ProjectController::class, 'edit']);

Route::post('/project/update/{id}', [ProjectController::class, 'update']);

Route::delete('/project/delete/{id}', [ProjectController::class, 'delete']);


