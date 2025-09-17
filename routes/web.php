<?php

use App\Events\TestEvent;
use App\Http\Controllers\LoadNoteMasiveController;
use App\Http\Controllers\MigrationController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\WebSocketController;
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


// Route::get('/loadNoteMasive', [LoadNoteMasiveController::class, 'process']);


Route::get('/trigger-event', function () {
    event(new TestEvent('Hello from Laravel Reverb!'));
    \Log::info('Event broadcasted');
    return response()->json(['status' => 'Event dispatched']);
});


Route::get('/actualizar', function () {

    // Array asociativo con cédulas como keys y nombres como values
$alumnosColegio = [
    '33988113' => 'ACEVEDO GUERRA, WILBER GERARDO',
    '33456080' => 'AMADO NARANJO, ANGELICA ISABEL',
    '33368095' => 'ARAOS CHAVEZ, MIAH VALENTINA',
    '33439758' => 'BARAJAS RONDON, YEISON STIDK',
    '33364141' => 'BARRIOS MORA, ARNALDO SEBASTIAN',
    '32932493' => 'CARDENAS ESPINEL, FRAHYLIN NESMAR',
    '33254296' => 'CATAÑO BALLESTEROS, ANGEL SANTIAGO',
    '33411107' => 'COLMENARES ODREMAN, AARON DAVID',
    '33367622' => 'CONTRERAS CONTRERAS, LUIS FERNANDO',
    '32792574' => 'CONTRERAS MORALES, LUCIANO ANDRES',
    '33536571' => 'DAZA RAMIREZ, WILFRANK FERNEY',
    '33731950' => 'DE LA HOZ PARADA, JORGE LUIS',
    '35066907' => 'GONZALEZ RAMIREZ, SINAI CAMILA',
    '34619439' => 'GUERRERO SANCHEZ, KAMILO ANDREY',
    '33439131' => 'LUCAS AYALA, ORIANA GABRIELA',
    '1091669507' => 'MARQUEZ JURGENSEN, JOSE GABRIEL',
    '33302824' => 'MOJICA LABRADOR, MARIANLLY ILEANA',
    '1091967697' => 'MORA DIAZ, YENIFER ANDREINA',
    '33498120' => 'MORA MORA, VERONICA VANESSA',
    '33439041' => 'MORENO RODRIGUEZ, LUZ ADRIANA',
    '32998425' => 'OLIVEROS COLMENARES, YUJEIRY NOEMI',
    '32835306' => 'PINEDA CONTRERAS, CRISTOPHER DANIEL',
    '32888995' => 'PORRAS DELGADO, KARLY VIVIANA',
    '33367717' => 'PULIDO URBINA, MARIANA',
    '33368342' => 'QUINTERO GARCIA, ANDRES SANTIAGO',
    '1091376363' => 'QUINTERO TAMI, CRISTOFER ALEJANDRO',
    '33271794' => 'RAMIREZ RANGEL, ISABELLA',
    '33456191' => 'RAMIREZ ZAMBRANO, DIEGO ALEJANDRO',
    '33439072' => 'ROA QUEVEDO, ARIADNA VANESSA',
    '32785741' => 'ROJAS ROJAS, JOSE ANGEL',
    '33456178' => 'ROLON ONTIVEROS, FABIAN ISAAC',
    '33254829' => 'RUEDA CARVAJAL, BREYNER JESUS',
    '32835613' => 'SANCHEZ MORA, MARIA ALEJANDRA',
    '32582797' => 'TAPIAS QUIROZ, NICOLLE GABRIELA',
    '32737232' => 'TARAZONA BARAJAS, YEIBERSON JOSUEPH'
];

    $company_id = 1;
    $type_education_id = 3; // bachillerato
    $grade_id = 14; // Grado
    $section_id = 4; // Sección

    $studentsActualizados = [];
    $studentsCreados = [];
    $cedulasNoEncontradas = [];

    // Buscar alumnos existentes en la base de datos
    foreach ($alumnosColegio as $cedula => $nombre) {
        // Buscar estudiante por cédula (usando LIKE por si tiene formato diferente)
        $student = Student::where('identity_document', 'LIKE', "%{$cedula}%")->first();

        if ($student) {
            // Actualizar alumno existente
            $student->update([
                'type_education_id' => $type_education_id,
                'grade_id' => $grade_id,
                'section_id' => $section_id,
                'company_id' => $company_id,
                'password' => bcrypt($cedula), // Agregar password con la cédula
            ]);
            $studentsActualizados[] = $student;
        } else {
            // Crear nuevo alumno
            $nuevoStudent = Student::create([
                'identity_document' => $cedula,
                'full_name' => $nombre,
                'type_education_id' => $type_education_id,
                'grade_id' => $grade_id,
                'section_id' => $section_id,
                'company_id' => $company_id,
                'password' => bcrypt($cedula), // Password con la cédula encriptada
            ]);
            $studentsCreados[] = $nuevoStudent;
            $cedulasNoEncontradas[] = $cedula;
        }
    }

    return [
        "total_alumnos_lista" => count($alumnosColegio),
        "cantidad_actualizados" => count($studentsActualizados),
        "cantidad_creados" => count($studentsCreados),
        "cedulas_no_encontradas" => $cedulasNoEncontradas,
        "students_actualizados" => $studentsActualizados,
        "students_creados" => $studentsCreados,
        "mensaje" => "Proceso completado. Alumnos actualizados: " . count($studentsActualizados) .
            ", Alumnos creados: " . count($studentsCreados),
    ];
});
 