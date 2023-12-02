<?php

use App\Http\Controllers\PassportAuthController;
use App\Http\Controllers\PermissionController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [PassportAuthController::class, 'register'])->name('register');
Route::post('/login', [PassportAuthController::class, 'login'])->name('login');

Route::post('/permission-list', [PermissionController::class, 'list'])->name('permission.list');

Route::get('/prueba', function () {
    return [1, 2, 3, 4];
});
