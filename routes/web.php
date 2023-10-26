<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;
use App\Models\Tenant;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/linkstorage', function () {
    $tenants = Tenant::get();
    $data = [];
    $data[public_path('storage')] = storage_path('app/public');

    foreach ($tenants as $tenant) {
        $data[public_path('storage_' . $tenant['id'])] = storage_path('storage_' . $tenant['id'] . '/app/public');
    }

    Config::set('filesystems.links', $data);
    Config::get('filesystems.links', 'public');

    return Artisan::call('storage:link');
});
