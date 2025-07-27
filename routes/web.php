<?php

use App\Http\Controllers\MigrationController;
use App\Http\Controllers\StudentController;
use App\Models\Note;
use App\Models\Student;
use Barryvdh\DomPDF\PDF;
use Illuminate\Support\Facades\Route;

Route::get('/pdf', function () {
    $pdf = app('dompdf.wrapper');

    // Datos del estudiante
    $student = (object)[
        'first_name' => 'Valeria Sofia',
        'last_name' => 'Ojeda Zambrano',
        'id_number' => '12.632.113',
        'grade' => 'CUARTO AÑO SECCIÓN A',
        'school_year' => '2020-2021'
    ];

    $next_school_year = '2025-2026';
    $solvencyCode = 'B4A-22';

    // Configurar el PDF
    $pdf = $pdf->loadView('SolvencyCertificate', compact('student', 'next_school_year', 'solvencyCode'))
        ->setPaper([0, 0, 595, 420], 'portrait'); // Mitad de una hoja A4 (595x420 en puntos)

    return $pdf->stream();
});

Route::get('/phpinfo', function () {
    // return phpversion();
    phpinfo();
    exit;
});

Route::get('/', function () {
    return view("welcome");
    // return Note::with(["student" => function ($query) {
    //     $query->where("type_education_id", 2);
    //     $query->where("grade_id", 8);
    // }])->whereHas("student", function ($query) {
    //     $query->where("type_education_id", 2);
    //     $query->where("grade_id", 8);
    // })->get();
    // return view('welcome');


    // Obtener los IDs de los estudiantes en el grado 8
    // $studentIds = Student::where('grade_id', 8)->pluck('id');

    // // Actualizar las notas de los estudiantes en el grado 8
    // Note::whereIn('student_id', $studentIds)
    //     ->each(function ($note) {
    //         $jsonData = $note->json; // Asumiendo que json es un atributo de Note
    //         $jsonArray = json_decode($jsonData, true);

    //         // Modificar los campos 3 y 4
    //         $jsonArray['3'] = '';
    //         $jsonArray['4'] = '';

    //         // Guardar los cambios
    //         $note->json = json_encode($jsonArray);
    //         $note->save();
    //     });

    return response()->json(['message' => 'Notes updated successfully']);
});

Route::get('/bd_table', [MigrationController::class, 'trasnferBD']);

Route::get('/updates', [MigrationController::class, 'updates']);

Route::get('/students/statistics', [StudentController::class, 'studentStatistics']);
