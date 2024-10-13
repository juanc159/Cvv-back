<?php

use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Student
|--------------------------------------------------------------------------
*/

Route::get('/student-list', [StudentController::class, 'list']);

Route::delete('/student-delete/{id}', [StudentController::class, 'delete']);

Route::post('/student-changeState', [StudentController::class, 'changeState']);

Route::get('/student-dataForm/{action}/{id?}', [StudentController::class, 'dataForm']);

Route::post('/student-create', [StudentController::class, 'store']);

Route::put('/student-update', [StudentController::class, 'store']);

Route::get('/student-planning/{id?}', [StudentController::class, 'planning']);

Route::post('/student-planningStore', [StudentController::class, 'planningStore']);

Route::get('/student-resetPassword/{id}', [StudentController::class, 'resetPassword']);

