<?php

use App\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;

// Rutas protegidas
Route::middleware(['check.permission:documents.menu'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Documentos commo certificados del estudiante
    |--------------------------------------------------------------------------
    */

    // Mostrar datos del estudiante
    Route::get('/documents/{id}/show', [DocumentController::class, 'show']);

    // Generar certificados
    Route::get('/documents/certificate/{id}', [DocumentController::class, 'certificate']);

    Route::get('/documents/certificate-completion/{id}', [DocumentController::class, 'certificateCompletion']);

    Route::get('/documents/proof-no-scholarship/{id}', [DocumentController::class, 'proofOfNotHavingScholarship']);

    Route::get('/documents/certificate-approval/{id}', [DocumentController::class, 'certificateApproval']);

    Route::get('/documents/certificate-good-conduct/{id}', [DocumentController::class, 'certificateOfGoodConduct']);

    Route::get('/documents/certificate-enrollment/{id}', [DocumentController::class, 'certificateOfEnrollment']);

    // Generar nuevos certificados
    Route::get('/documents/certificate-transporter/{id}', [DocumentController::class, 'certificateTransporter']);

    Route::get('/documents/certificate-enrollment-amount/{id}', [DocumentController::class, 'certificateEnrollmentAmount']);

    Route::get('/documents/certificate-withdrawal/{id}', [DocumentController::class, 'certificateWithdrawal']);

    Route::get('/documents/absence-permission/{id}', [DocumentController::class, 'absencePermission']);
});


Route::get('/documents/prosecutionInitialEducation', [DocumentController::class, 'prosecutionInitialEducation']);

Route::get('/documents/certificateInitialEducation', [DocumentController::class, 'certificateInitialEducation']);

Route::get('/documents/prosecutionPrimaryEducation', [DocumentController::class, 'prosecutionPrimaryEducation']);
