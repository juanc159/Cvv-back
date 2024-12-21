<?php

use App\Http\Controllers\NoteController;
use App\Http\Controllers\TypeEducationNoteSelectionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Notes
|--------------------------------------------------------------------------
*/

Route::post('/note-blockPayrollUpload', [NoteController::class, 'blockPayrollUpload']);

Route::get('/note-downloadAllConsolidated', [NoteController::class, 'downloadAllConsolidated']);

Route::post('/note-resetOptionDownloadPdf', [NoteController::class, 'resetOptionDownloadPdf']);

/*
|--------------------------------------------------------------------------
| Type Educations
|--------------------------------------------------------------------------
*/
Route::get('/type_educations/visualization/show', [TypeEducationNoteSelectionController::class, 'show']);
Route::post('/type_educations/visualization/store', [TypeEducationNoteSelectionController::class, 'store']);
 