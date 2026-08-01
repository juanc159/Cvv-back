<?php

use App\Http\Controllers\MaintenanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mantenimiento
|--------------------------------------------------------------------------
| Auditorías técnicas del sistema. Todo de solo lectura.
*/

Route::middleware(['check.permission:maintenance.files'])->group(function () {

    Route::get('/maintenance/file-audit/data', [MaintenanceController::class, 'fileAuditData']);

    Route::post('/maintenance/file-audit/run', [MaintenanceController::class, 'runFileAudit']);

    Route::post('/maintenance/file-audit/cleanup', [MaintenanceController::class, 'runCleanup']);

    Route::get('/maintenance/file-audit/{id}', [MaintenanceController::class, 'showAudit']);
});

Route::middleware(['check.permission:maintenance.data'])->group(function () {

    Route::get('/maintenance/data-audit/data', [MaintenanceController::class, 'dataAuditData']);

    Route::post('/maintenance/data-audit/run', [MaintenanceController::class, 'runDataAudit']);

    Route::get('/maintenance/data-audit/{id}', [MaintenanceController::class, 'showAudit']);
});
