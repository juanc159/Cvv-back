<?php

use App\Http\Controllers\PassportAuthController;
use App\Http\Controllers\PwController;
use App\Http\Controllers\UserController;
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
Route::get('/pw-dataPrincipal', [PwController::class, 'dataPrincipal']);
// Principal

// School
Route::get('/pw-dataSchool/{id}', [PwController::class, 'dataSchool']);
// School

// School
Route::get('/pw-dataGradeSection/{school_id}/{grade_id}/{section_id}', [PwController::class, 'dataGradeSection']);
// School

// School
Route::get('/pw-dataGradeSectionNotes/{school_id}/{grade_id}/{section_id}', [PwController::class, 'dataGradeSectionNotes']);
// School

Route::get('/pw-pdfNote/{id}', [PwController::class, 'pdfPNote']);


Route::get('/pw-linksMenu/{company_id}', [PwController::class, 'linksMenu']);
Route::get('/pw-banners/{company_id}', [PwController::class, 'banners']);
Route::get('/pw-teachers/{company_id}', [PwController::class, 'teachers']);
Route::get('/pw-contactData/{company_id}', [PwController::class, 'contactData']);
Route::get('/pw-services/{company_id}', [PwController::class, 'services']);
Route::get('/pw-service/{service_id}', [PwController::class, 'service']);

Route::get('/pw-materiaPendiente/{company_id}', [PwController::class, 'materiaPendiente']);
 
Route::post('/user/changePassword', [UserController::class, 'changePassword']);
