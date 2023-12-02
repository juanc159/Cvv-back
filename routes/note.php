<?php

use App\Http\Controllers\NoteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Notes
|--------------------------------------------------------------------------
*/

Route::get('/note-dataForm', [NoteController::class, 'dataForm'])->name('note.dataForm');
Route::post('/note-store', [NoteController::class, 'store'])->name('note.store');
