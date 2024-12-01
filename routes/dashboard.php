<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

Route::get('/dashboard/countAllData', [DashboardController::class, 'countAllData']);
Route::get('/dashboard/studentByTypeEducation', [DashboardController::class, 'studentByTypeEducation']);
Route::get('/dashboard/studentByPhotoStatus', [DashboardController::class, 'studentByPhotoStatus']);
