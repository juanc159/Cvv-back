<?php

use App\Http\Controllers\MigrationController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/bd_table', [MigrationController::class, 'trasnferBD']);

Route::get('/updates', [MigrationController::class, 'updates']);

Route::get('/students/statistics', [StudentController::class, 'studentStatistics']);
