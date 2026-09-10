<?php

use App\Http\Controllers\StudentPromotionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Promoción de alumnos (cambio de año escolar)
|--------------------------------------------------------------------------
|
| Reemplaza la ruta manual /actualizar de web.php. El proceso es en dos pasos:
| preview analiza el archivo sin escribir nada, apply confirma lo aprobado.
|
*/

Route::middleware(['check.permission:studentPromotion.list'])->group(function () {

    Route::get('/studentPromotion/dataForm', [StudentPromotionController::class, 'dataForm']);

    Route::get('/studentPromotion/history', [StudentPromotionController::class, 'history']);

    Route::post('/studentPromotion/preview', [StudentPromotionController::class, 'preview']);

    Route::get('/studentPromotion/{id}', [StudentPromotionController::class, 'show']);

    Route::get('/studentPromotion/{id}/export', [StudentPromotionController::class, 'export']);

    Route::post('/studentPromotion/{id}/apply', [StudentPromotionController::class, 'apply']);

    Route::post('/studentPromotion/{id}/revert', [StudentPromotionController::class, 'revert']);

    Route::post('/studentPromotion/{id}/discard', [StudentPromotionController::class, 'discard']);
});
