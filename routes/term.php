<?php

use App\Http\Controllers\TermController;
use Illuminate\Support\Facades\Route;

// Rutas protegidas
Route::middleware(['check.permission:term.list'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Term
    |--------------------------------------------------------------------------
    */

    Route::get('/term/paginate', [TermController::class, 'paginate']);

    Route::get('/term/create', [TermController::class, 'create']);

    Route::post('/term/store', [TermController::class, 'store']);

    Route::get('/term/{id}/edit', [TermController::class, 'edit']);

    Route::post('/term/update/{id}', [TermController::class, 'update']);

    Route::delete('/term/delete/{id}', [TermController::class, 'delete']);

    Route::post('/term/changeStatus', [TermController::class, 'changeStatus']);
});
