<?php

use App\Http\Controllers\PendingRegistrationController;
use Illuminate\Support\Facades\Route;

// Rutas protegidas
Route::middleware(['check.permission:pendingRegistration.list'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | PendingRegistration
    |--------------------------------------------------------------------------
    */

    Route::get('/pendingRegistration/paginate', [PendingRegistrationController::class, 'paginate']);

    Route::get('/pendingRegistration/create', [PendingRegistrationController::class, 'create']);

    Route::post('/pendingRegistration/store', [PendingRegistrationController::class, 'store']);

    Route::get('/pendingRegistration/{id}/edit', [PendingRegistrationController::class, 'edit']);

    Route::post('/pendingRegistration/update/{id}', [PendingRegistrationController::class, 'update']);

    Route::delete('/pendingRegistration/delete/{id}', [PendingRegistrationController::class, 'delete']); 
});
