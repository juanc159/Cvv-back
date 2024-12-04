<?php

use App\Http\Controllers\NoteController;
use App\Http\Controllers\PassportAuthController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::get('/register', [PassportAuthController::class, 'register']);
Route::post('login', [PassportAuthController::class, 'login']);

Route::get('/teacher-downloadConsolidated/{id}', [TeacherController::class, 'downloadConsolidated']);
Route::get('/teacher-planningShow/{id?}', [TeacherController::class, 'planning'])->name('teacher.planning');

Route::get('/note-dataForm', [NoteController::class, 'dataForm']);
Route::post('/note-store', [NoteController::class, 'store']);


Route::post('/user/changePassword', [UserController::class, 'changePassword']);
