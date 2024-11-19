<?php

namespace App\Http\Controllers;

use App\Helpers\Constants;
use App\Models\BlockData;
use App\Models\Grade;
use App\Models\Section;
use App\Repositories\BlockDataRepository;
use App\Repositories\NoteRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TypeEducationRepository;
use App\Repositories\UserRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Cache;
use Throwable;

class NoteController extends Controller
{
    public function __construct(
        private TypeEducationRepository $typeEducationRepository,
        private StudentRepository $studentRepository,
        private NoteRepository $noteRepository,
        private UserRepository $userRepository,
        private BlockDataRepository $blockDataRepository,
    ) {
        $this->typeEducationRepository = $typeEducationRepository;
        $this->studentRepository = $studentRepository;
        $this->noteRepository = $noteRepository;
        $this->userRepository = $userRepository;
    }

    public function dataForm()
    {
        Cache::put('Cache_Grade', Grade::get(), now()->addMinutes(60));
        Cache::put('Cache_Section', Section::get(), now()->addMinutes(60));

        $typeEducations = $this->typeEducationRepository->selectList();
        $blockData = BlockData::where("name", "BLOCK_PAYROLL_UPLOAD")->first()->is_active;


        return response()->json([
            'typeEducations' => $typeEducations,
            'blockData' => $blockData,
        ]);
    }

    public function store(Request $request)
    {
        try {
            // $this->studentRepository->deleteData();

            DB::beginTransaction();

            if ($request->hasFile('archive')) {
                $file = $request->file('archive');
                $import = Excel::toArray([], $file);

                $typeEducation = $this->typeEducationRepository->find($request->input("type_education_id"), ["grades.subjects"]);

                $sheets = count($import);
                for ($j = 0; $j < $sheets; $j++) {

                    for ($i = 0; $i < $typeEducation->cantNotes; $i++) {
                        // Suponiendo que solo hay una hoja en el archivo Excel
                        $data = $import[$j];

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


                             $groupedCedulas = collect($formattedData)
                                ->filter(function ($item) {
                                    return !is_null($item["CÉDULA"]); // Filtrar elementos con cédulas no nulas
                                })
                                ->groupBy('AÑO') // Agrupar por AÑO
                                ->map(function ($yearGroup) {
                                    return $yearGroup->groupBy('SECCIÓN') // Agrupar por SECCIÓN dentro de cada AÑO
                                        ->map(function ($sectionGroup) {
                                            return $sectionGroup->pluck("CÉDULA")->filter()->values(); // Extraer cédulas
                                        });
                                });


                        foreach ($groupedCedulas as $key => $value) {
                            // $grade = Grade::where("name", $key)->first();
                            $grade = $this->grade($key, "name");
                            if ($grade) {
                                foreach ($value as $key2 => $value2) {
                                    // $section = Section::where("name", trim($key2))->first();
                                    $section = $this->section($key2, "name");
                                    if ($section) {
                                        $this->studentRepository->deleteDataArray([
                                            "company_id" => $request->input("company_id"),
                                            "identity_document" => $value2,
                                            "type_education_id" => $request->input("type_education_id"),
                                            "grade_id" => $grade->id,
                                            "section_id" => $section->id,
                                        ]);
                                    }
                                }
                            }
                        }

                        // Obtener todos los estudiantes cuyos cedulas NO están en el array

                        $formattedData = array_map(function ($item) {
                            return array_map('trim', $item); // Aplica trim a cada valor del item
                        }, $formattedData);


                        foreach ($formattedData as $row) {
                            if (!empty($row["CÉDULA"])) {

                                // $grade = Grade::where("name", $row["AÑO"])->first();
                                // $section = Section::where("name", $row["SECCIÓN"])->first();

                                $grade = $this->grade($row["AÑO"], "name");
                                $section = $this->section($row["SECCIÓN"], "name");


                                $student = $this->studentRepository->searchOne([
                                    "identity_document" => $row["CÉDULA"]
                                ]);



                                $model = [
                                    "id" => $student ? $student->id : null,
                                    "company_id" => $request->input("company_id"),
                                    "type_education_id" => $request->input("type_education_id"),
                                    "grade_id" => $grade?->id,
                                    "section_id" => $section?->id,
                                    "identity_document" => $row["CÉDULA"],
                                    "password" => $row["CÉDULA"],
                                    "full_name" => $row["NOMBRES Y APELLIDOS ESTUDIANTE"],
                                    "pdf" => isset($row["PDF"]) && $row["PDF"] == 1 ? 1 : 0,
                                ];

                                if ($student) {
                                    unset($model["password"]);
                                }
                                $student = $this->studentRepository->store($model);




                                $grade = $typeEducation->grades->where("id", $grade->id)->first();

                                $subjects = $grade->subjects;



                                foreach ($subjects as $key => $sub) {



                                    $model2 = [
                                        "student_id" => $student->id,
                                        "subject_id" => $sub->id,
                                    ];

                                    $note = $this->noteRepository->searchOne($model2);
                                    $json = null;

                                    if ($note) {
                                        $json = json_decode($note->json, 1);
                                    }


                                    $model2 = [
                                        "id" => $note ? $note->id : null,
                                        "student_id" => $student->id,
                                        "subject_id" => $sub->id,
                                    ];


                                    for ($xx = 1; $xx <= $typeEducation->cantNotes; $xx++) {
                                        $json[$xx] = isset($row[$sub->code . $xx]) ? trim($row[$sub->code . $xx]) : (isset($json[$xx]) ? $json[$xx] : null);
                                    }


                                    // if ($row["CÉDULA"] = "34158972") {
                                    //     return  $json;
                                    // }


                                    $model2["json"] = json_encode($json);

                                      $this->noteRepository->store($model2);
                                }
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

    function grade($value, $field)
    {
        $cache = collect(Cache::get('Cache_Grade'));
        $data = $cache->first(function ($item) use ($value, $field) {
            return strtoupper($item[$field]) === strtoupper($value);
        });
        return $data;
    }

    function section($value, $field)
    {
        $cache = collect(Cache::get('Cache_Section'));
        $data = $cache->first(function ($item) use ($value, $field) {
            return strtoupper($item[$field]) === strtoupper($value);
        });
        return $data;
    }

    public function blockPayrollUpload(Request $request)
    {
        try {
            DB::beginTransaction();

            $model = BlockData::where("name", "BLOCK_PAYROLL_UPLOAD")->first();
            $model->is_active = $request->input('value');
            $model->save();

            ($model->is_active == 1) ? $msg = 'Activado' : $msg = 'Inactivado';

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Carga de archivos ' . $msg . ' con éxito']);
        } catch (Throwable $th) {
            DB::rollback();

            return response()->json(['code' => 500, 'message' => $th->getMessage()]);
        }
    }
}
