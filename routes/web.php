<?php
 
use App\Http\Controllers\MigrationController; 
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/bd_table', [MigrationController::class, 'trasnferBD']);

