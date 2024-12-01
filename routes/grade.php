<?php

use App\Http\Controllers\GradeController;
use Illuminate\Support\Facades\Route;

//Rutas protegidas
Route::middleware(['check.permission:grade.list'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Grade
    |--------------------------------------------------------------------------
    */

    Route::get('/grade/list', [GradeController::class, 'list']);

    Route::get('/grade/create', [GradeController::class, 'create']);

    Route::post('/grade/store', [GradeController::class, 'store']);

    Route::get('/grade/{id}/edit', [GradeController::class, 'edit']);

    Route::post('/grade/update/{id}', [GradeController::class, 'update']);

    Route::delete('/grade/delete/{id}', [GradeController::class, 'delete']);

    Route::post('/grade/changeStatus', [GradeController::class, 'changeStatus']);
});
