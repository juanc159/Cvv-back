<?php

use App\Http\Controllers\Usercontroller;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User
|--------------------------------------------------------------------------
*/
Route::post('/user-list', [Usercontroller::class, 'list'])->name('user.list');

Route::delete('/user-delete/{id}', [Usercontroller::class, 'delete'])->name('user.delete');

Route::post('/user-changeState', [Usercontroller::class, 'changeState'])->name('api.user.changeState');

Route::get('/user-dataForm/{action}/{id?}', [Usercontroller::class, 'dataForm'])->name('api.permission.dataForm');

Route::post('/user-create', [Usercontroller::class, 'store'])->name('user.store');

Route::put('/user-update', [Usercontroller::class, 'store'])->name('user.update');

