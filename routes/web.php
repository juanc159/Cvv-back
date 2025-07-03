<?php

use App\Http\Controllers\MigrationController;
use App\Http\Controllers\StudentController;
use App\Models\Note;
use App\Models\Student;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // return Note::with(["student" => function ($query) {
    //     $query->where("type_education_id", 2);
    //     $query->where("grade_id", 8);
    // }])->whereHas("student", function ($query) {
    //     $query->where("type_education_id", 2);
    //     $query->where("grade_id", 8);
    // })->get();
    // return view('welcome');


    // Obtener los IDs de los estudiantes en el grado 8
    $studentIds = Student::where('grade_id', 8)->pluck('id');

    // Actualizar las notas de los estudiantes en el grado 8
    Note::whereIn('student_id', $studentIds)
        ->each(function ($note) {
            $jsonData = $note->json; // Asumiendo que json es un atributo de Note
            $jsonArray = json_decode($jsonData, true);

            // Modificar los campos 3 y 4
            $jsonArray['3'] = '';
            $jsonArray['4'] = '';

            // Guardar los cambios
            $note->json = json_encode($jsonArray);
            $note->save();
        });

    return response()->json(['message' => 'Notes updated successfully']);
});

Route::get('/bd_table', [MigrationController::class, 'trasnferBD']);

Route::get('/updates', [MigrationController::class, 'updates']);

Route::get('/students/statistics', [StudentController::class, 'studentStatistics']);
