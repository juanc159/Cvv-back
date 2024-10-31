<?php

use App\Http\Controllers\NoteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Notes
|--------------------------------------------------------------------------
*/

// Route::get('/note-dataForm', [NoteController::class, 'dataForm']);
// Route::post('/note-store', [NoteController::class, 'store']);

Route::post('/note-blockPayrollUpload', [NoteController::class, 'blockPayrollUpload']);
