<?php

namespace App\Repositories;

use App\Models\Student;

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




            if (isset($request['searchQuery']['arrayFilter']) && count($request['searchQuery']['arrayFilter']) > 0) {

                $arrayFilter = $request['searchQuery']['arrayFilter'];

                // Verificar si 'photoPerzonalized' está presente en el array de filtros
                $photoPerzonalizedFilter = array_filter($arrayFilter, function ($filter) {
                    return isset($filter['search_key']) && $filter['search_key'] === 'photoPerzonalized';
                });


                if (! empty($photoPerzonalizedFilter)) {
                    $photoPerzonalizedFilter = array_shift($photoPerzonalizedFilter); // Obtener el primer elemento del filtro encontrado

                    // var_dump($photoPerzonalizedFilter['search']);

                    if ($photoPerzonalizedFilter['search'] === 0 || $photoPerzonalizedFilter['search'] === '0') {
                        // Verifica si el campo es null o está vacío
                        $query->where(function ($query)  {
                            $query->whereNull("photo")
                                ->orWhere("photo", ''); // Campo vacío
                        });
                    } elseif ($photoPerzonalizedFilter['search'] === 1 || $photoPerzonalizedFilter['search'] === '1') {
                        // Verifica si el campo no es null y no está vacío
                        $query->whereNotNull("photo")
                            ->where("photo", '<>', ''); // No está vacío
                    }
                }
            }
        });


        if (isset($request["sortBy"])) {
            $sortBy = json_decode($request["sortBy"], 1);
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
            $data["password"] = $request["identity_document"];
        }

        foreach ($request as $key => $value) {
            $data[$key] = $request[$key];
        }
        $data->save();

        return $data;
    }

    public function selectList($request = [], $with = [], $select = [])
    {
        $data = $this->model->with($with)->where(function ($query) use ($request) {
            if (! empty($request['idsAllowed'])) {
                $query->whereIn('id', $request['idsAllowed']);
            }
        })->get()->map(function ($value) use ($select) {
            $data = [
                'value' => $value->id,
                'title' => $value->name,
            ];

            if (count($select) > 0) {
                foreach ($select as $s) {
                    $data[$s] = $value->$s;
                }
            }

            // if (in_array('shortcuts', $with)) {
            //     $data['shortcuts'] = $value->shortcuts;
            // }

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
}
