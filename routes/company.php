<?php

use App\Http\Controllers\CompanyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Company
|--------------------------------------------------------------------------
*/
Route::post('/company-list', [CompanyController::class, 'list'])->name('company.list');

Route::delete('/company-delete/{id}', [CompanyController::class, 'delete'])->name('company.delete');

Route::post('/company-changeState', [CompanyController::class, 'changeState'])->name('api.company.changeState');

Route::get('/company-dataForm/{action}/{id?}', [CompanyController::class, 'dataForm'])->name('api.permission.dataForm');

Route::post('/company-create', [CompanyController::class, 'store'])->name('company.store');

Route::put('/company-update', [CompanyController::class, 'store'])->name('company.update');

