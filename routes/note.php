<?php

use App\Http\Controllers\NoteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Notes
|--------------------------------------------------------------------------
*/

Route::post('/note-blockPayrollUpload', [NoteController::class, 'blockPayrollUpload']);

Route::get('/note-downloadAllConsolidated', [NoteController::class, 'downloadAllConsolidated']);
