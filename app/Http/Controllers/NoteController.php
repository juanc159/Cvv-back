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

            $this->studentRepository->deleteData();

            DB::beginTransaction();

            if ($request->hasFile('archive')) {
                $file = $request->file('archive');
                $import = Excel::toArray([], $file);

                $typeEducation = $this->typeEducationRepository->find($request->input("type_education_id"), ["grades.subjects"]);

                for ($i = 0; $i < $typeEducation->cantNotes; $i++) {
                    // Suponiendo que solo hay una hoja en el archivo Excel
                    $data = $import[$i];

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
                        if (!empty(trim($row["CÉDULA"]))) {
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
                            $student = $this->studentRepository->store($model);

                            $grade = $typeEducation->grades->where("id", $grade->id)->first();
                            $subjects = $grade->subjects;

                            foreach ($subjects as $key => $sub) {

                                $model2 = [
                                    "student_id" => $student->id,
                                    "subject_id" => $sub->id,
                                ];
                                $json = null;
                                for ($i = 1; $i <= $typeEducation->cantNotes; $i++) {
                                    $json[$i] = isset($row[$sub->code . $i]) ?  trim($row[$sub->code .  $i]) : null;
                                }
                                $model2["json"] = json_encode($json);

                                $this->noteRepository->store($model2);
                            }
                        }
                    }
                }
            }

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Registros guardados correctamente', 'data' => $data]);
        } catch (Exception $th) {
            DB::rollBack();

            return response()->json(['code' => 500, 'message' => $th->getMessage(), 'line' => $th->getLine()], 500);
        }
    }
}
