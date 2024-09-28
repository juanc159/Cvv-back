<?php

use App\Http\Controllers\PassportAuthController;
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


Route::get('/pw-pdfNote/{id}', [PwController::class, 'pdfPNote'])->name('pw.pdfPNote');

Route::get('/pw-socialNetworks/{company_id}', [PwController::class, 'socialNetworks'])->name('pw.socialNetworks');
Route::get('/pw-banners/{company_id}', [PwController::class, 'banners'])->name('pw.banners');
Route::get('/pw-teachers/{company_id}', [PwController::class, 'teachers'])->name('pw.teachers');
Route::get('/pw-contactData/{company_id}', [PwController::class, 'contactData'])->name('pw.contactData');
Route::get('/pw-services/{company_id}', [PwController::class, 'services'])->name('pw.services');
Route::get('/pw-service/{service_id}', [PwController::class, 'service'])->name('pw.service');



Route::get('/pw-materiaPendiente', [PwController::class, 'materiaPendiente']);




Route::post('/changePassword', [PassportAuthController::class, 'changePassword']);
