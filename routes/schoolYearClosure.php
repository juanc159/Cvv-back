<?php

use App\Http\Controllers\SchoolYearClosureController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Cierre del año escolar
|--------------------------------------------------------------------------
|
| Decide qué son los alumnos que quedaron sin grado ni sección después de promover:
| pendientes de inscribirse, retirados o egresados.
|
*/

Route::middleware(['check.permission:studentPromotion.closure'])->group(function () {

    Route::get('/schoolYearClosure', [SchoolYearClosureController::class, 'index']);

    Route::post('/schoolYearClosure/classify', [SchoolYearClosureController::class, 'classify']);
});
