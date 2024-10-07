<?php

use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Student
|--------------------------------------------------------------------------
*/

Route::get('/student-list', [StudentController::class, 'list']);

Route::get('/student-resetPassword/{id}', [StudentController::class, 'resetPassword']);
