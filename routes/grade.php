<?php

use App\Http\Controllers\GradeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Grade
|--------------------------------------------------------------------------
*/

Route::post('/grade-list', [GradeController::class, 'list'])->name('grade.list');

Route::delete('/grade-delete/{id}', [GradeController::class, 'delete'])->name('grade.delete');

Route::post('/grade-changeState', [GradeController::class, 'changeState'])->name('api.banner.changeState');

Route::get('/grade-dataForm/{action}/{id?}', [GradeController::class, 'dataForm'])->name('api.permission.dataForm');

Route::post('/grade-create', [GradeController::class, 'store'])->name('grade.store');

Route::put('/grade-update', [GradeController::class, 'store'])->name('grade.update');
