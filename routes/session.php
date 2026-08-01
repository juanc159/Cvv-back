<?php

use App\Http\Controllers\PassportAuthController;
use Illuminate\Support\Facades\Route;

// Cerrar todas las sesiones activas (solo Super Administrador).
Route::post('/sessions/revoke-all', [PassportAuthController::class, 'revokeAllSessions']);
