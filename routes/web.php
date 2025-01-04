<?php

use App\Events\UserConnected;
use App\Http\Controllers\MigrationController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/bd_table', [MigrationController::class, 'trasnferBD']);



 
Route::get('/prueba', function () {
    $userRecord = User::first();
    event(new UserConnected($userRecord));
    
});

