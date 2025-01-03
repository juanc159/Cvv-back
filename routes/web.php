<?php
 
use App\Http\Controllers\MigrationController; 
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/bd_table', [MigrationController::class, 'trasnferBD']);



 
// Route::get('/broadcasting/auth', function () {
//     return 'Broadcasting auth route is working';
// });