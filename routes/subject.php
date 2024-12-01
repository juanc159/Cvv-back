<?php

use App\Http\Controllers\SubjectController;
use Illuminate\Support\Facades\Route;

//Rutas protegidas
Route::middleware(['check.permission:subject.list'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Subject
    |--------------------------------------------------------------------------
    */

    Route::get('/subject/list', [SubjectController::class, 'list']);

    Route::get('/subject/create', [SubjectController::class, 'create']);

    Route::post('/subject/store', [SubjectController::class, 'store']);

    Route::get('/subject/{id}/edit', [SubjectController::class, 'edit']);

    Route::post('/subject/update/{id}', [SubjectController::class, 'update']);

    Route::delete('/subject/delete/{id}', [SubjectController::class, 'delete']);

    Route::post('/subject/changeStatus', [SubjectController::class, 'changeStatus']);
});
