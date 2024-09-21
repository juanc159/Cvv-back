<?php

use App\Http\Controllers\RoleController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
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
// Tenants
Route::post('/tenant-store', [TenantController::class, 'store'])->name('tenant.store');
Route::post('/tenant-list', [TenantController::class, 'list'])->name('tenant.list');
// Tenants

// Usuarios
// Route::post('/user-store', [UserController::class, 'store'])->name('user.store');
// Route::post('/user-list', [UserController::class, 'list'])->name('user.list');
Route::post('/user-listpermissions', [UserController::class, 'listPermissions'])->name('user.listPermissions');
Route::post('/user-storepermissions', [UserController::class, 'storePermissions'])->name('user.storePermissions');
// Usuarios

// Roles
Route::get('/role-dataForm', [RoleController::class, 'dataForm'])->name('settings.role.dataForm');
Route::post('/role-store', [RoleController::class, 'store'])->name('settings.role.store');
Route::post('/role-list', [RoleController::class, 'list'])->name('settings.role.list');
Route::get('/role-info/{id}', [RoleController::class, 'info'])->name('settings.role.info');
Route::delete('/role-delete/{id}', [RoleController::class, 'delete'])->name('settings.role.delete');
// Roles

Route::get('/linkstorage', function () {
    Artisan::call('storage:link');
});



Route::get('/teacher-planningShow/{id?}', [TeacherController::class, 'planning'])->name('teacher.planning');
