<?php

namespace App\Repositories;

use App\Models\Student;
use App\Models\TypeEducation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class StudentRepository extends BaseRepository
{
    public function __construct(Student $modelo)
    {
        parent::__construct($modelo);
    }

    public function list($request = [], $with = [], $select = ['*'])
    {
        $data = $this->model->select($select)->with($with)->where(function ($query) use ($request) {
            filterComponent($query, $request);

            if (! empty($request['name'])) {
                $query->where('name', 'like', '%' . $request['name'] . '%');
            }

            if (! empty($request['company_id'])) {
                $query->where('company_id', $request['company_id']);
            }
            if (! empty($request['type_education_id'])) {
                $query->where('type_education_id', $request['type_education_id']);
            }

            if (! empty($request['grade_id'])) {
                $query->where('grade_id', $request['grade_id']);
            }
            if (! empty($request['section_id'])) {
                $query->where('section_id', $request['section_id']);
            }

            // **Excluir estudiantes retirados**
            $query->whereDoesntHave('withdrawal');

            if (isset($request['searchQuery']['arrayFilter']) && count($request['searchQuery']['arrayFilter']) > 0) {

                $arrayFilter = $request['searchQuery']['arrayFilter'];

                // Verificar si 'photoPerzonalized' está presente en el array de filtros
                $photoPerzonalizedFilter = array_filter($arrayFilter, function ($filter) {
                    return isset($filter['search_key']) && $filter['search_key'] === 'photoPerzonalized';
                });

                if (! empty($photoPerzonalizedFilter)) {
                    $photoPerzonalizedFilter = array_shift($photoPerzonalizedFilter); // Obtener el primer elemento del filtro encontrado

                    if ($photoPerzonalizedFilter['search'] === 0 || $photoPerzonalizedFilter['search'] === '0') {
                        $query->where(function ($query) {
                            $query->whereNull('photo')
                                ->orWhere('photo', ''); // Campo vacío
                        });
                    } elseif ($photoPerzonalizedFilter['search'] === 1 || $photoPerzonalizedFilter['search'] === '1') {
                        $query->whereNotNull('photo')
                            ->where('photo', '<>', ''); // No está vacío
                    }
                }
            }
        });

        if (isset($request['sortBy'])) {
            $sortBy = json_decode($request['sortBy'], 1);
            foreach ($sortBy as $key => $value) {
                $data = $data->orderBy($value['key'], $value['order']);
            }
        }

        if (empty($request['typeData'])) {
            $data = $data->paginate($request['perPage'] ?? 10);
        } else {
            $data = $data->get();
        }

        return $data;
    }

    public function store($request)
    {
        $request = $this->clearNull($request);

        if (! empty($request['id'])) {
            $data = $this->model->find($request['id']);
        } else {
            $data = $this->model::newModelInstance();
            $data['password'] = $request['identity_document'];
        }

        foreach ($request as $key => $value) {
            $data[$key] = $request[$key];
        }
        $data->save();

        return $data;
    }

    public function selectList($request = [], $with = [], $select = [], $fieldValue = 'id', $fieldTitle = 'name')
    {
        $data = $this->model->with($with)->where(function ($query) use ($request) {
            if (! empty($request['idsAllowed'])) {
                $query->whereIn('id', $request['idsAllowed']);
            }
        })->get()->map(function ($value) use ($with, $select, $fieldValue, $fieldTitle) {
            $data = [
                'value' => $value->$fieldValue,
                'title' => $value->$fieldTitle,
            ];

            if (count($select) > 0) {
                foreach ($select as $s) {
                    $data[$s] = $value->$s;
                }
            }

            if (count($with) > 0) {
                foreach ($with as $s) {
                    $data[$s] = $value->$s;
                }
            }

            return $data;
        });

        return $data;
    }

    public function deleteDataArray($request = [])
    {
        return $data = $this->model->where(function ($query) use ($request) {
            if (! empty($request['company_id'])) {
                $query->where('company_id', $request['company_id']);
            }
            if (! empty($request['type_education_id'])) {
                $query->where('type_education_id', $request['type_education_id']);
            }
            if (! empty($request['grade_id'])) {
                $query->where('grade_id', $request['grade_id']);
            }
            if (! empty($request['section_id'])) {
                $query->where('section_id', $request['section_id']);
            }
            if (! empty($request['identity_document'])) {
                $query->whereNotIn('identity_document', $request['identity_document']);
            }
        })->delete();
    }

    public function searchOne($request = [], $with = [], $select = ['*'])
    {
        $data = $this->model->select($select)->with($with)->where(function ($query) use ($request) {
            if (! empty($request['company_id'])) {
                $query->where('company_id', $request['company_id']);
            }
            if (! empty($request['identity_document'])) {
                $query->where('identity_document', $request['identity_document']);
            }
            if (! empty($request['type_education_id'])) {
                $query->where('type_education_id', $request['type_education_id']);
            }
            if (! empty($request['grade_id'])) {
                $query->where('grade_id', $request['grade_id']);
            }
            if (! empty($request['section_id'])) {
                $query->where('section_id', $request['section_id']);
            }
        });

        $data = $data->first();

        return $data;
    }

    public function searchUser($request = [])
    {
        $data = $this->model->where(function ($query) use ($request) {
            $query->where('identity_document', $request['user']);
        })->first();

        return $data;
    }

    public function countData($request = [])
    {
        // Total de estudiantes
        $totalStudents = $this->model->where(function ($query) use ($request) {
            if (!empty($request['company_id'])) {
                $query->where('company_id', $request['company_id']);
            }
        })->count();

        // Estudiantes activos (los que NO están retirados)
        $activeStudents = $this->model->where(function ($query) use ($request) {
            if (!empty($request['company_id'])) {
                $query->where('company_id', $request['company_id']);
            }
        })->whereDoesntHave('withdrawal') // Filtrar los que NO tienen un retiro
            ->count();

        // Estudiantes retirados (los que SÍ están en la tabla de retiros)
        $withdrawnStudents = $this->model->whereHas('withdrawal', function ($query) use ($request) {
            if (!empty($request['company_id'])) {
                $query->where('company_id', $request['company_id']);
            }
        })->count();

        return [
            'total' => $totalStudents,
            'active' => $activeStudents,
            'withdrawn' => $withdrawnStudents
        ];
    }


    public function getCountByTypeEducation($request = [])
    {
        $data = $this->model
            ->select('type_education_id', 'type_education.name', DB::raw('count(*) as total'))
            ->join('type_education', 'students.type_education_id', '=', 'type_education.id') // Realizamos el join con la tabla 'type_education'
            ->where('students.is_active', true)
            ->where('company_id', $request['company_id'])
            ->groupBy('type_education_id', 'type_education.name') // Agrupamos también por el nombre del tipo de educación
            ->get();

        return $data;
    }

    public function getCountByPhotoStatus(array $params)
    {
        $query = $this->model->where('company_id', $params['company_id'])
            ->where('is_active', 1);

        // Filtrar por el estado de la foto
        if (isset($params['has_photo'])) {
            if ($params['has_photo']) {
                // Si tiene foto, filtrar por los estudiantes que tienen foto (no nulo y no vacío)
                $query->whereNotNull('photo')->where('photo', '!=', '');  // Foto no nula y no vacía
            } else {
                // Si no tiene foto, filtrar por los estudiantes que NO tienen foto (nulo o vacío)
                $query->where(function ($query) {
                    $query->whereNull('photo')->orWhere('photo', '');
                });
            }
        }

        return $query->count();  // Devolver el conteo de estudiantes
    }

    public function studentStatisticsData(Request $request)
    {
        $companyId = $request->input('company_id');

        // Obtener rango de fechas para el periodo seleccionado
        $dateInitial = Carbon::parse($request->input('dateInitial', "2000-01-01"));
        $dateEnd = Carbon::parse($request->input('dateEnd', "2099-12-31"));

        $typeEducations = TypeEducation::with(['grades.sections'])->get();

        // $typeEducations = TypeEducation::with(['grades.sections' => function ($query) {
        //     $query->whereDoesntHave('subjects', function ($subQuery) {
        //         $subQuery->where('id', 33); // Omitir las secciones con la materia con id 33
        //     });
        // }])->get();

        $type_education_id = $typeEducations->pluck('id')->toArray();

        $allCombinations = collect();

        foreach ($typeEducations as $key => $typeEducation) {

            // Verificamos si el tipo de educación tiene grados
            if ($typeEducation->grades->isEmpty()) {
                // Si no tiene grados, agregamos la combinación con un valor por defecto o nulo
                $allCombinations->push([
                    'type_education_name' => $typeEducation->name,
                    'grade_name' => 'No tiene grados', // Valor por defecto cuando no hay grados
                    'section_name' => 'No tiene secciones', // Valor por defecto cuando no hay secciones
                ]);
            } else {
                foreach ($typeEducation->grades as $grade) {
                    // Verificamos si el grado tiene secciones
                    if ($grade->sections->isEmpty()) {
                        // Si no tiene secciones, agregamos la combinación con un valor por defecto
                        $allCombinations->push([
                            'type_education_name' => $typeEducation->name,
                            'grade_name' => $grade->name,
                            'section_name' => 'No tiene secciones', // Valor por defecto cuando no hay secciones
                        ]);
                    } else {
                        foreach ($grade->sections as $section) {
                            // Si tiene secciones, agregamos la combinación de type_education, grade y section
                            $allCombinations->push([
                                'type_education_name' => $typeEducation->name,
                                'grade_name' => $grade->name,
                                'section_name' => $section->name,
                            ]);
                        }
                    }
                }
            }
        }




        // Consulta Matrícula Inicial (estudiantes activos antes del periodo)
        $initialMatriculation = Student::selectRaw('
        type_education.name as type_education_name,
        grades.name as grade_name,
        sections.name as section_name,
        COUNT(*) as total,
        SUM(CASE WHEN students.gender = "M" THEN 1 ELSE 0 END) as male,
        SUM(CASE WHEN students.gender = "F" THEN 1 ELSE 0 END) as female,

        SUM(CASE WHEN students.country_id != companies.country_id THEN 1 ELSE 0 END) as total_foreign,
       SUM(CASE WHEN students.gender = "M" AND students.country_id != companies.country_id THEN 1 ELSE 0 END) as male_foreign,
       SUM(CASE WHEN students.gender = "F" AND students.country_id != companies.country_id THEN 1 ELSE 0 END) as female_foreign
    ')
            ->join('grades', 'students.grade_id', '=', 'grades.id')
            ->join('sections', 'students.section_id', '=', 'sections.id')
            ->join('type_education', 'students.type_education_id', '=', 'type_education.id')
            ->join('companies', 'students.company_id', '=', 'companies.id')
            ->where('students.company_id', $companyId)
            ->whereIn('students.type_education_id', $type_education_id)
            ->where('students.real_entry_date', '<', $dateInitial)
            ->whereNotExists(function ($query) use ($dateInitial) {
                $query->select(DB::raw(1))
                    ->from('student_withdrawals')
                    ->whereRaw('student_withdrawals.student_id = students.id')
                    ->where('student_withdrawals.date', '<', $dateInitial);
            })
            ->groupBy('type_education.name', 'grades.name', 'sections.name')
            ->get();

        // Consulta Nuevos Ingresos (matriculados en el periodo actual)
        $newEntries = Student::selectRaw('
        type_education.name as type_education_name,
        grades.name as grade_name,
        sections.name as section_name,
        COUNT(*) as total,
        SUM(CASE WHEN students.gender = "M" THEN 1 ELSE 0 END) as male,
        SUM(CASE WHEN students.gender = "F" THEN 1 ELSE 0 END) as female,


        SUM(CASE WHEN students.country_id != companies.country_id THEN 1 ELSE 0 END) as total_foreign,
       SUM(CASE WHEN students.gender = "M" AND students.country_id != companies.country_id THEN 1 ELSE 0 END) as male_foreign,
       SUM(CASE WHEN students.gender = "F" AND students.country_id != companies.country_id THEN 1 ELSE 0 END) as female_foreign
    ')
            ->join('grades', 'students.grade_id', '=', 'grades.id')
            ->join('sections', 'students.section_id', '=', 'sections.id')
            ->join('type_education', 'students.type_education_id', '=', 'type_education.id')
            ->join('companies', 'students.company_id', '=', 'companies.id')
            ->where('students.company_id', $companyId)
            ->whereIn('students.type_education_id', $type_education_id)
            ->whereBetween('students.real_entry_date', [$dateInitial, $dateEnd])
            ->groupBy('type_education.name', 'grades.name', 'sections.name')
            ->get();

        // Consulta Egresos (retirados en el periodo actual)
        $withdrawnStudents = Student::selectRaw('
        type_education.name as type_education_name,
        grades.name as grade_name,
        sections.name as section_name,
        COUNT(*) as total,
        SUM(CASE WHEN students.gender = "M" THEN 1 ELSE 0 END) as male,
        SUM(CASE WHEN students.gender = "F" THEN 1 ELSE 0 END) as female,

        SUM(CASE WHEN students.country_id != companies.country_id THEN 1 ELSE 0 END) as total_foreign,
       SUM(CASE WHEN students.gender = "M" AND students.country_id != companies.country_id THEN 1 ELSE 0 END) as male_foreign,
       SUM(CASE WHEN students.gender = "F" AND students.country_id != companies.country_id THEN 1 ELSE 0 END) as female_foreign
    ')
            ->join('student_withdrawals', 'students.id', '=', 'student_withdrawals.student_id')
            ->join('grades', 'students.grade_id', '=', 'grades.id')
            ->join('sections', 'students.section_id', '=', 'sections.id')
            ->join('type_education', 'students.type_education_id', '=', 'type_education.id')
            ->join('companies', 'students.company_id', '=', 'companies.id')
            ->where('students.company_id', $companyId)
            ->whereIn('students.type_education_id', $type_education_id)
            ->whereDate('student_withdrawals.date', ">=", $dateInitial)
            ->whereDate('student_withdrawals.date', "<=", $dateEnd)
            ->groupBy('type_education.name', 'grades.name', 'sections.name')
            ->get();

        // Inicializar el array de estadísticas con todas las combinaciones posibles
        $statistics = [];
        foreach ($allCombinations as $combination) {
            $key = $combination['type_education_name'] . '-' . $combination['grade_name'] . '-' . $combination['section_name'];
            $statistics[$key] = [
                'type_education_name' => $combination['type_education_name'],
                'grade_name' => $combination['grade_name'],
                'section_name' => $combination['section_name'],
                'initial' => [
                    'total' => 0,
                    'male' => 0,
                    'female' => 0,
                    'total_foreign' => 0,
                    'male_foreign' => 0,
                    'female_foreign' => 0,

                ],
                'new_entries' => [
                    'total' => 0,
                    'male' => 0,
                    'female' => 0,
                    'total_foreign' => 0,
                    'male_foreign' => 0,
                    'female_foreign' => 0,
                ],
                'withdrawals' => [
                    'total' => 0,
                    'male' => 0,
                    'female' => 0,
                    'total_foreign' => 0,
                    'male_foreign' => 0,
                    'female_foreign' => 0,
                ],
                'current' => [
                    'total' => 0,
                    'male' => 0,
                    'female' => 0,
                ],
                'foreign' => [
                    'total' => 0,
                    'male' => 0,
                    'female' => 0,
                ],
            ];
        }

        // Procesar matrícula inicial
        foreach ($initialMatriculation as $item) {
            $key = $item->type_education_name . '-' . $item->grade_name . '-' . $item->section_name;
            if (isset($statistics[$key])) {
                $statistics[$key]['initial'] = [
                    'total' => $item->total,
                    'male' => $item->male,
                    'female' => $item->female,
                    'total_foreign' => $item->total_foreign,
                    'male_foreign' => $item->male_foreign,
                    'female_foreign' => $item->female_foreign,
                ];
            }
        }

        // Procesar nuevos ingresos
        foreach ($newEntries as $item) {
            $key = $item->type_education_name . '-' . $item->grade_name . '-' . $item->section_name;
            if (isset($statistics[$key])) {
                $statistics[$key]['new_entries'] = [
                    'total' => $item->total,
                    'male' => $item->male,
                    'female' => $item->female,
                    'total_foreign' => $item->total_foreign,
                    'male_foreign' => $item->male_foreign,
                    'female_foreign' => $item->female_foreign,
                ];
            }
        }

        // Procesar egresos
        foreach ($withdrawnStudents as $item) {
            $key = $item->type_education_name . '-' . $item->grade_name . '-' . $item->section_name;
            if (isset($statistics[$key])) {
                $statistics[$key]['withdrawals'] = [
                    'total' => $item->total,
                    'male' => $item->male,
                    'female' => $item->female,
                    'total_foreign' => $item->total_foreign,
                    'male_foreign' => $item->male_foreign,
                    'female_foreign' => $item->female_foreign,
                ];
            }
        }

        // Calcular matrícula actual
        foreach ($statistics as $key => $stat) {
            $statistics[$key]['current'] = [
                'total' => $stat['initial']['total'] + $stat['new_entries']['total'] - $stat['withdrawals']['total'],
                'male' => $stat['initial']['male'] + $stat['new_entries']['male'] - $stat['withdrawals']['male'],
                'female' => $stat['initial']['female'] + $stat['new_entries']['female'] - $stat['withdrawals']['female']
            ];
        }

        // Procesar extrangeros
        foreach ($statistics as $key => $stat) { 
             $statistics[$key]['foreign'] = [
                'total' => $stat['initial']['total_foreign'] + $stat['new_entries']['total_foreign'] - $stat['withdrawals']['total_foreign'],
                'male' => $stat['initial']['male_foreign'] + $stat['new_entries']['male_foreign'] - $stat['withdrawals']['male_foreign'],
                'female' => $stat['initial']['female_foreign'] + $stat['new_entries']['female_foreign'] - $stat['withdrawals']['female_foreign']
            ];
        } 

        // Nueva consulta para estudiantes retirados
        $withdrawnStudents = Student::select([
            'students.identity_document',
            'students.full_name',
            'students.birthday',
            'students.gender',
            'grades.name as grade_name',
            'sections.name as section_name',
            'student_withdrawals.date as withdrawal_date',
            'student_withdrawals.reason'
        ])
            ->join('student_withdrawals', 'students.id', '=', 'student_withdrawals.student_id')
            ->join('grades', 'students.grade_id', '=', 'grades.id')
            ->join('sections', 'students.section_id', '=', 'sections.id')
            ->join('type_education', 'students.type_education_id', '=', 'type_education.id')
            ->where('students.company_id', $companyId)
            ->whereDate('student_withdrawals.date', ">=", $dateInitial)
            ->whereDate('student_withdrawals.date', "<=", $dateEnd)
            ->whereIn('students.type_education_id', $type_education_id)
            ->orderBy('student_withdrawals.date', 'desc')
            ->get();


        // Consulta Nuevos Ingresos (matriculados en el periodo actual)
        $entriesStudents = Student::selectRaw('
                students.identity_document,
                students.full_name,
                students.birthday,
                students.gender,
                type_education.name as type_education_name,
                grades.name as grade_name,
                sections.name as section_name
            ')
            ->join('grades', 'students.grade_id', '=', 'grades.id')
            ->join('sections', 'students.section_id', '=', 'sections.id')
            ->join('type_education', 'students.type_education_id', '=', 'type_education.id')
            ->where('students.company_id', $companyId)
            ->whereIn('students.type_education_id', $type_education_id)
            ->whereBetween('students.real_entry_date', [$dateInitial, $dateEnd])
            ->orderBy('students.real_entry_date', 'desc')
            ->get();



        return [
            "statistics" => $statistics,
            "withdrawnStudents" => $withdrawnStudents,
            "entriesStudents" => $entriesStudents,
        ];
    }
 
}
