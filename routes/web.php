<?php

use App\Http\Controllers\MigrationController;
use App\Http\Controllers\StudentController;
use App\Models\Note;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Note::with(["student" => function ($query) {
        $query->where("type_education_id", 3);
    }])->whereHas("student", function ($query) {
        $query->where("type_education_id", 3);
    })->get();
    return view('welcome');
});

Route::get('/bd_table', [MigrationController::class, 'trasnferBD']);

Route::get('/updates', [MigrationController::class, 'updates']);

Route::get('/students/statistics', [StudentController::class, 'studentStatistics']);
