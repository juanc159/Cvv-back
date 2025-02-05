<?php

namespace App\Repositories;

use App\Models\Student;
use Illuminate\Support\Facades\DB;

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
}
