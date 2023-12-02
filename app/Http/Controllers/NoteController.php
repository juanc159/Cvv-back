<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\Section;
use App\Models\TypeEducation;
use App\Repositories\NoteRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TypeEducationRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class NoteController extends Controller
{
    private $typeEducationRepository;
    private $studentRepository;
    private $noteRepository;

    public function __construct(
        TypeEducationRepository $typeEducationRepository,
        StudentRepository $studentRepository,
        NoteRepository $noteRepository,
    ) {
        $this->typeEducationRepository = $typeEducationRepository;
        $this->studentRepository = $studentRepository;
        $this->noteRepository = $noteRepository;
    }

    public function dataForm()
    {
        $typeEducations = $this->typeEducationRepository->selectList();

        return response()->json([
            'typeEducations' => $typeEducations,
        ]);
    }

    public function store(Request $request)
    {
        try {

            $this->studentRepository->truncate();
            $this->noteRepository->truncate();
            DB::beginTransaction();

            if ($request->hasFile('archive')) {
                $file = $request->file('archive');
                $import = Excel::toArray([], $file);

                // Suponiendo que solo hay una hoja en el archivo Excel
                $data = $import[0];

                // Obtener las claves y eliminarlas de $data
                $keys = array_shift($data);
                $formattedData = [];


                foreach ($data as $row) {
                    $formattedRow = [];
                    foreach ($keys as $index => $key) {
                        $formattedRow[$key] = $row[$index] ?? null;
                    }
                    $formattedData[] = $formattedRow;
                }

                foreach ($formattedData as $row) {
                    $grade = Grade::where("name", trim($row["AÑO"]))->first();
                    $section = Section::where("name", trim($row["SECCIÓN"]))->first();
                    $model = [
                        "company_id" => $request->input("company_id"),
                        "type_education_id" => $request->input("type_education_id"),
                        "grade_id" => $grade?->id,
                        "section_id" => $section?->id,
                        "identity_document" => trim($row["CÉDULA"]),
                        "full_name" => trim($row["NOMBRES Y APELLIDOS ESTUDIANTE"]),
                    ];
                    // return $model;
                    $student = $this->studentRepository->store($model);

                    $typeEducation = TypeEducation::with(["subjects"])->find($request->input('type_education_id'));

                    $subjects = $typeEducation->subjects;

                    // return $row;
                    foreach ($subjects as $key => $sub) {
                        $model2 = [
                            "student_id" => $student->id,
                            "subject_id" => $sub->id,
                            "value1" => isset($row[$sub->code . "1"]) ?  trim($row[$sub->code . "1"]) : null,
                            "value2" => isset($row[$sub->code . "2"]) ?  trim($row[$sub->code . "2"]) : null,
                            "value3" => isset($row[$sub->code . "3"]) ? trim($row[$sub->code . "3"]) : null,
                            "value4" => isset($row[$sub->code . "4"]) ?  trim($row[$sub->code . "4"]) : null,
                        ];

                        $note = $this->noteRepository->store($model2);
                    }
                }


                // return $formattedData;
            }


            //     // $data contiene los datos del archivo Excel
            //     // Puedes recorrer $data para procesar la información como desees

            //     // Ejemplo de cómo recorrer los datos
            //     // foreach ($data as $row) {
            //     //     foreach ($row as $cell) {
            //     //         // Hacer algo con cada celda
            //     //         echo $cell . " ";
            //     //     }
            //     //     echo "<br>";
            //     // }


            // $data = $this->noteRepository->store($request->all());


            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Registros guardados correctamente', 'data' => $data]);
        } catch (Exception $th) {
            DB::rollBack();

            return response()->json(['code' => 500, 'message' => $th->getMessage(), 'line' => $th->getLine()], 500);
        }
    }
}
