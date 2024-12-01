<?php

use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

//Rutas protegidas
Route::middleware(['check.permission:student.list'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Student
    |--------------------------------------------------------------------------
    */

    Route::get('/student/list', [StudentController::class, 'list']);

    Route::get('/student/create', [StudentController::class, 'create']);

    Route::post('/student/store', [StudentController::class, 'store']);

    Route::get('/student/{id}/edit', [StudentController::class, 'edit']);

    Route::post('/student/update/{id}', [StudentController::class, 'update']);

    Route::delete('/student/delete/{id}', [StudentController::class, 'delete']);

    Route::post('/student/changeStatus', [StudentController::class, 'changeStatus']);

    Route::get('/student-resetPassword/{id}', [StudentController::class, 'resetPassword']);
});
