<?php

use App\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;

//Rutas protegidas
Route::middleware(['check.permission:teacher.list'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Teacher
    |--------------------------------------------------------------------------
    */

    Route::get('/teacher/list', [TeacherController::class, 'list']);

    Route::get('/teacher/create', [TeacherController::class, 'create']);

    Route::post('/teacher/store', [TeacherController::class, 'store']);

    Route::get('/teacher/{id}/edit', [TeacherController::class, 'edit']);

    Route::post('/teacher/update/{id}', [TeacherController::class, 'update']);

    Route::delete('/teacher/delete/{id}', [TeacherController::class, 'delete']);

    Route::post('/teacher/changeStatus', [TeacherController::class, 'changeStatus']);

    Route::get('/teacher-resetPassword/{id}', [TeacherController::class, 'resetPassword']);

    Route::get('/teacher-planning/{id?}', [TeacherController::class, 'planning']);

    Route::post('/teacher-planningStore', [TeacherController::class, 'planningStore']);

    Route::put('/teachers/order', [TeacherController::class, 'updateOrder']);
});
