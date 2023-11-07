<?php

use App\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Subject
|--------------------------------------------------------------------------
*/

Route::post('/teacher-list', [TeacherController::class, 'list'])->name('teacher.list');

Route::delete('/teacher-delete/{id}', [TeacherController::class, 'delete'])->name('teacher.delete');

Route::post('/teacher-changeState', [TeacherController::class, 'changeState'])->name('api.banner.changeState');

Route::get('/teacher-dataForm/{action}/{id?}', [TeacherController::class, 'dataForm'])->name('api.permission.dataForm');

Route::post('/teacher-create', [TeacherController::class, 'store'])->name('teacher.store');

Route::put('/teacher-update', [TeacherController::class, 'store'])->name('teacher.update');
