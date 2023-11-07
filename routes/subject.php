<?php

use App\Http\Controllers\SubjectController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Subject
|--------------------------------------------------------------------------
*/

Route::post('/subject-list', [SubjectController::class, 'list'])->name('subject.list');

Route::delete('/subject-delete/{id}', [SubjectController::class, 'delete'])->name('subject.delete');

Route::post('/subject-changeState', [SubjectController::class, 'changeState'])->name('api.banner.changeState');

Route::get('/subject-dataForm/{action}/{id?}', [SubjectController::class, 'dataForm'])->name('api.permission.dataForm');

Route::post('/subject-create', [SubjectController::class, 'store'])->name('subject.store');

Route::put('/subject-update', [SubjectController::class, 'store'])->name('subject.update');
