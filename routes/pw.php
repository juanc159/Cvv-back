<?php

use App\Http\Controllers\PwController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Principal
Route::get('/pw-dataPrincipal', [PwController::class, 'dataPrincipal'])->name('pw.dataPrincipal');
// Principal

// School
Route::get('/pw-dataSchool/{id}', [PwController::class, 'dataSchool'])->name('pw.dataSchool');
// School

// School
Route::get('/pw-dataGradeSection/{school_id}/{grade_id}/{section_id}', [PwController::class, 'dataGradeSection'])->name('pw.dataGradeSection');
// School

// School
Route::get('/pw-dataGradeSectionNotes/{school_id}/{grade_id}/{section_id}', [PwController::class, 'dataGradeSectionNotes'])->name('pw.dataGradeSectionNotes');
// School
