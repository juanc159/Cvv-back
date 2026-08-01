<?php

use App\Http\Controllers\ActivityController;
use Illuminate\Support\Facades\Route;

// Rutas protegidas

/*
|--------------------------------------------------------------------------
| Activity
|--------------------------------------------------------------------------
*/

Route::get('/activity/list', [ActivityController::class, 'list']);

Route::get('/activity/create', [ActivityController::class, 'create']);

Route::post('/activity/store', [ActivityController::class, 'store']);

Route::get('/activity/{id}/edit', [ActivityController::class, 'edit']);

Route::post('/activity/update/{id}', [ActivityController::class, 'update']);

Route::delete('/activity/delete/{id}', [ActivityController::class, 'delete']);

Route::post('/activity/changeStatus', [ActivityController::class, 'changeStatus']);

Route::get('/activity/pending', [ActivityController::class, 'pending']);


// Rutas de Estudiante
Route::get('/student/activity/{id}', [ActivityController::class, 'showForStudent']);

Route::post('/student/activity/submit', [ActivityController::class, 'submitActivity']);

Route::get('/teacher/activities/{id}/submissions', [ActivityController::class, 'getSubmissions']);

Route::put('/teacher/submissions/{id}/status', [ActivityController::class, 'evaluateSubmission']);

// 1. Carga la lista general y el dashboard (Ligero)
Route::get('/teacher/activities/{id}/submissions', [ActivityController::class, 'getSubmissionsList']);

// 2. Carga el historial completo de un alumno específico bajo demanda (Pesado)
Route::get('/teacher/activities/{activity_id}/students/{student_id}/history', [ActivityController::class, 'getStudentHistory']);