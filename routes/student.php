<?php

use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

// Rutas protegidas
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

    Route::get('/student/show/{id}', [StudentController::class, 'show']);

    Route::post('/student/withdraw', [StudentController::class, 'withdraw']);

    Route::get('/students/statistics', [StudentController::class, 'studentStatistics']);

    Route::post('/students/statisticsExcelExport', [StudentController::class, 'statisticsExcelExport']);
});


Route::post('/student/saveLiterals', [StudentController::class, 'saveLiterals']);
