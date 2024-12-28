<?php

use App\Http\Controllers\BackupController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Backup
|--------------------------------------------------------------------------
*/

Route::post('/backup', [BackupController::class, 'blockPayrollUpload']);
 