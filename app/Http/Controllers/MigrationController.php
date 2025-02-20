<?php

namespace App\Http\Controllers;

use App\Helpers\Constants;
use App\Imports\StudentsImport;
use App\Models\Banner;
use App\Models\BlockData;
use App\Models\Company;
use App\Models\CompanyDetail;
use App\Models\Grade;
use App\Models\GradeSubject;
use App\Models\JobPosition;
use App\Models\Note;
use App\Models\Section;
use App\Models\Service;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherComplementary;
use App\Models\TeacherPlanning;
use App\Models\TypeDetail;
use App\Models\TypeEducation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MigrationController extends Controller
{
    private $bd_old;

    public function __construct()
    {
        $this->bd_old = 'mysql_old';
    }

    public function truncate($table)
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table($table)->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function transferData($tableOld, $tableNew)
    {
        $noTruncate = ['users'];
        if (! in_array($tableNew, $noTruncate)) {
            $this->truncate($tableNew);
        }

        // return DB::connection('mysql_old')->table($tableOld)->get();

        DB::connection('mysql_old')->table($tableOld)->orderBy('id')->chunk(10, function ($datosChunk) use ($tableNew) {

            DB::beginTransaction();
            foreach ($datosChunk as $dato) {
                $this->$tableNew($dato);
            }
            DB::commit();
        });
    }

    public function trasnferBD()
    {
        set_time_limit(999999999);
        try {

            // $otherTables = [
            //     "type_education",
            // ];

            // foreach ($otherTables as $key => $value) {
            //     $this->truncate($value);
            //     $this->$value();
            // }

            $tables = [
                // [
                //     'tableOld' => 'type_education',
                //     'tableNew' => 'type_education',
                // ],
                // [
                //     'tableOld' => 'type_details',
                //     'tableNew' => 'type_details',
                // ],
                // [
                //     'tableOld' => 'sections',
                //     'tableNew' => 'sections',
                // ],
                // [
                //     'tableOld' => 'block_data',
                //     'tableNew' => 'block_data',
                // ],
                // [
                //     'tableOld' => 'companies',
                //     'tableNew' => 'companies',
                // ],
                // [
                //     'tableOld' => 'company_details',
                //     'tableNew' => 'company_details',
                // ],
                // [
                //     'tableOld' => 'banners',
                //     'tableNew' => 'banners',
                // ],
                // [
                //     'tableOld' => 'grades',
                //     'tableNew' => 'grades',
                // ],
                // [
                //     'tableOld' => 'subjects',
                //     'tableNew' => 'subjects',
                // ],
                // [
                //     'tableOld' => 'grade_subjects',
                //     'tableNew' => 'grade_subjects',
                // ],
                // [
                //     'tableOld' => 'students',
                //     'tableNew' => 'students',
                // ],
                // [
                //     'tableOld' => 'job_positions',
                //     'tableNew' => 'job_positions',
                // ],
                // [
                //     'tableOld' => 'teachers',
                //     'tableNew' => 'teachers',
                // ],
                // [
                //     'tableOld' => 'teacher_plannings',
                //     'tableNew' => 'teacher_plannings',
                // ],
                // [
                //     'tableOld' => 'teacher_complementaries',
                //     'tableNew' => 'teacher_complementaries',
                // ],
                // [
                //     'tableOld' => 'services',
                //     'tableNew' => 'services',
                // ],
                [
                    'tableOld' => 'notes',
                    'tableNew' => 'notes',
                ],
            ];

            $response = [];

            foreach ($tables as $key => $value) {

                $this->transferData($value['tableOld'], $value['tableNew']);

                $datosDestino = DB::table($value['tableNew'])->get();

                $response[][$value['tableNew']] = $datosDestino;
            }

            return response()->json([
                'response' => $response,
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'code' => 500,
                'message' => 'Algo Ocurrio, Comunicate Con El Equipo De Desarrollo',
                'error' => $th->getMessage(),
                'line' => $th->getLine(),
            ], 500);
        }
    }

    //////////////////////////////////TABLAS/////////////////////////

    public function type_education($dato)
    {
        $model = new TypeEducation;

        $model->id = $dato->id;
        $model->name = $dato->name;
        $model->cantNotes = $dato->cantNotes;
        $model->save();
    }

    public function type_details($dato)
    {
        $model = new TypeDetail;

        $model->id = $dato->id;
        $model->name = $dato->name;
        $model->save();
    }

    public function sections($dato)
    {
        $model = new Section;

        $model->id = $dato->id;
        $model->name = $dato->name;
        $model->save();
    }

    public function block_data($dato)
    {
        $model = new BlockData;

        $model->id = $dato->id;
        $model->name = $dato->name;
        $model->is_active = $dato->is_active;
        $model->save();
    }

    public function companies($dato)
    {
        $model = new Company;

        $model->id = $dato->id;
        $model->name = $dato->name;
        $model->slogan = $dato->slogan;
        $model->image_principal = $dato->image_principal;
        $model->iframeGoogleMap = $dato->iframeGoogleMap;
        // $model->is_active = $dato->is_active;
        $model->save();
    }

    public function company_details($dato)
    {
        $model = new CompanyDetail;

        $model->id = $dato->id;
        $model->company_id = $dato->company_id;
        $model->type_detail_id = $dato->type_detail_id;
        $model->icon = $dato->icon;
        $model->color = $dato->color;
        $model->content = $dato->content;
        $model->save();
    }

    public function banners($dato)
    {
        $model = new Banner;

        $model->id = $dato->id;
        $model->company_id = $dato->company_id;
        $model->path = $dato->path;
        $model->save();
    }

    public function grades($dato)
    {
        $model = new Grade;

        $model->id = $dato->id;
        $model->company_id = $dato->company_id;
        $model->type_education_id = $dato->type_education_id;
        $model->name = $dato->name;
        $model->save();
    }

    public function subjects($dato)
    {
        $model = new Subject;

        $model->id = $dato->id;
        $model->company_id = $dato->company_id;
        $model->type_education_id = $dato->type_education_id;
        $model->name = $dato->name;
        $model->code = $dato->code;
        $model->save();
    }

    public function grade_subjects($dato)
    {
        $model = new GradeSubject;

        $model->id = $dato->id;
        $model->company_id = $dato->company_id;
        $model->grade_id = $dato->grade_id;
        $model->subject_id = $dato->subject_id;
        $model->save();
    }

    public function students($dato)
    {
        $model = new Student;

        $model->id = $dato->id;
        $model->company_id = $dato->company_id;
        $model->type_education_id = $dato->type_education_id;
        $model->grade_id = $dato->grade_id;
        $model->section_id = $dato->section_id;
        $model->identity_document = $dato->identity_document;
        $model->full_name = $dato->full_name;
        $model->pdf = $dato->pdf;
        $model->photo = $dato->photo;
        $model->password = $dato->password;
        $model->first_time = $dato->first_time;
        $model->save();
    }

    public function job_positions($dato)
    {
        $model = new JobPosition;

        $model->id = $dato->id;
        $model->name = $dato->name;
        $model->save();
    }

    public function teachers($dato)
    {
        $model = new Teacher;

        $model->id = $dato->id;
        $model->company_id = $dato->company_id;
        $model->type_education_id = $dato->type_education_id;
        $model->job_position_id = $dato->job_position_id;
        $model->name = $dato->name;
        $model->last_name = $dato->last_name;
        $model->email = $dato->email;
        $model->phone = $dato->phone;
        $model->photo = $dato->photo;
        $model->password = $dato->password ?? 123456;
        $model->order = $dato->order;
        $model->deleted_at = $dato->deleted_at;
        $model->save();
    }

    public function teacher_plannings($dato)
    {
        $model = new TeacherPlanning;

        $model->id = $dato->id;
        $model->teacher_id = $dato->teacher_id;
        $model->grade_id = $dato->grade_id;
        $model->section_id = $dato->section_id;
        $model->subject_id = $dato->subject_id;
        $model->path = $dato->path;
        $model->name = $dato->name;
        $model->save();
    }

    public function teacher_complementaries($dato)
    {
        $model = new TeacherComplementary;

        $model->id = $dato->id;
        $model->teacher_id = $dato->teacher_id;
        $model->grade_id = $dato->grade_id;
        $model->section_id = $dato->section_id;
        $model->subject_ids = $dato->subject_ids;
        $model->save();
    }

    public function services($dato)
    {
        $model = new Service;

        $model->id = $dato->id;
        $model->company_id = $dato->company_id;
        $model->title = $dato->title;
        $model->image = $dato->image;
        $model->html = $dato->html;
        $model->save();
    }

    public function notes($dato)
    {
        $model = new Note;

        $model->id = $dato->id;
        $model->student_id = $dato->student_id;
        $model->subject_id = $dato->subject_id;
        $model->json = $dato->json;
        $model->save();
    }

    public function users($dato)
    {
        $partesNombre = explode(' ', $dato->name);

        $model = new User;

        $model->id = $dato->id;
        $model->name = $partesNombre[0];
        $model->surname = isset($partesNombre[1]) ? $partesNombre[1] : '';
        $model->email = $dato->email;
        $model->password = $dato->password;
        $model->role_id = Constants::ROLE_SUPERADMIN_UUID;
        $model->company_id = Constants::COMPANY_UUID;
        $model->save();
    }


    public function updates(Request $request)
    {
        $files = [
            '4b-primaria22222.xlsx',
            // 'todo-primaria.xlsx',
            // 'todo-mediageneral.xlsx',
        ];

        // 3. Procesar todas las filas
        $updated = 0;
        $errors = [];
        $duplicates = [];

        foreach ($files as $key => $file) {
            // 1. Leer el archivo Excel
            $rows = Excel::toCollection(new StudentsImport, $file, 'public', \Maatwebsite\Excel\Excel::XLSX)->first();

            // 2. Identificar duplicados en el CSV para reportar
            $matriculas = $rows->countBy('identity_document');
            $duplicates = [...$duplicates, ...$matriculas->filter(fn($c) => $c > 1)->keys()];



            foreach ($rows as $row) {
                try {
                    DB::beginTransaction();

                    // Buscar TODOS los estudiantes coincidentes
                    $students = Student::where('identity_document', 'LIKE', "%{$row['identity_document']}%")->get();

                    if ($students->isEmpty()) {
                        $errors[] = [
                            'nombre_del_alumno' => $row['names'] . ' ' . $row["surnames"],
                            'matricula' => $row['identity_document'],
                            'error' => 'No encontrado'
                        ];
                        DB::commit();
                        continue;
                    }

                    // Actualizar todos los registros encontrados
                    foreach ($students as $student) {
                        $student->update([
                            'gender' => $row['gender'],
                            'country_id' => $row['country_id'],
                            'state_id' => $row['state_id'],
                            'city_id' => $row['city_id'],
                            'birthday' => $row['birthday'],
                        ]);
                    }

                    $updated += $students->count();
                    DB::commit();
                } catch (\Exception $e) {
                    $errors[] = [
                        'matricula' => $row['identity_document'],
                        'error' => $e->getMessage()
                    ];
                    DB::rollback();
                }
            }
        }



        // 4. Retornar respuesta
        return response()->json([
            'success' => true,
            'updated' => $updated,
            'duplicates' => $duplicates,
            'errors' => $errors
        ]);
    }

    private function mapGender($sexo)
    {
        return match (strtoupper($sexo)) {
            'M' => 'male',
            'F' => 'female',
            default => 'other'
        };
    }
}
