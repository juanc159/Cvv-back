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

Route::post('/teacher-changeState', [TeacherController::class, 'changeState'])->name('teacherr.changeState');

Route::get('/teacher-dataForm/{action}/{id?}', [TeacherController::class, 'dataForm'])->name('teacher.dataForm');

Route::post('/teacher-create', [TeacherController::class, 'store'])->name('teacher.store');

Route::put('/teacher-update', [TeacherController::class, 'store'])->name('teacher.update');


Route::get('/teacher-planning/{id?}', [TeacherController::class, 'planning'])->name('teacher.planning');
Route::post('/teacher-planningStore', [TeacherController::class, 'planningStore'])->name('teacher.planningStore');
