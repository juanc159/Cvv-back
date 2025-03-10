<?php

use App\Http\Controllers\PendingRegistrationAttemptController;
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

    Route::get('/pendingRegistration/{id}/show', [PendingRegistrationController::class, 'show']);

    Route::post('/pendingRegistration/update/{id}', [PendingRegistrationController::class, 'update']);

    Route::delete('/pendingRegistration/delete/{id}', [PendingRegistrationController::class, 'delete']);



    /*
    |--------------------------------------------------------------------------
    | PendingRegistrationAttempts
    |--------------------------------------------------------------------------
    */

    Route::get('pendingRegistration/attempts/{pending_registration_id}', [PendingRegistrationAttemptController::class, 'index']);

    Route::post('pendingRegistration/attempts/store', [PendingRegistrationAttemptController::class, 'store']);

    Route::get('pendingRegistration/attempts/{id}/edit', [PendingRegistrationAttemptController::class, 'edit']);

    Route::post('pendingRegistration/attempts/update/{id}', [PendingRegistrationAttemptController::class, 'update']); 
});
