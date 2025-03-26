<?php

use App\Http\Controllers\TodoController;
use Illuminate\Support\Facades\Route;
 

/*
|--------------------------------------------------------------------------
| Todo
|--------------------------------------------------------------------------
*/

Route::get('/todo/list', [TodoController::class, 'list']);

Route::get('/todo/create', [TodoController::class, 'create']);

Route::post('/todo/store', [TodoController::class, 'store']);

Route::get('/todo/{id}/edit', [TodoController::class, 'edit']);

Route::post('/todo/update/{id}', [TodoController::class, 'update']);

Route::delete('/todo/delete/{id}', [TodoController::class, 'delete']);

Route::post('/todo/changeStatus', [TodoController::class, 'changeStatus']);
