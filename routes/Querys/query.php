<?php

use App\Http\Controllers\QueryController;
use Illuminate\Support\Facades\Route;

// Lista de Pais, Departamentos y Ciudades
Route::get('/selectCountries', [QueryController::class, 'selectCountries'])->name('selectCountries');
Route::get('/selectDepartments/{country_id}', [QueryController::class, 'selectDepartments'])->name('selectDepartments');
Route::get('/selectCities/{department_id}', [QueryController::class, 'selectCities'])->name('selectCities');
// Lista de Pais, Departamentos y Ciudades
