<?php

namespace App\Repositories;

use App\Models\Grade;

class GradeRepository extends BaseRepository
{
    public function __construct(Grade $modelo)
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
            if (isset($request['state'])) {
                if ($request['state'] === '0' || $request['state'] === '1') {
                    $query->where('state', $request['state']);
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

    public function teachers($request = [])
    {
        $data = $this->model->where(function ($query) {
            $query->whereHas('teachers');
        })->get()->map(function ($value) {
            return [
                'grade_id' => $value->id,
                'grade_name' => $value->name,
                'teachers' => $value->teachers->map(function ($val) {
                    return [
                        'section_id' => $val->section_id,
                        'section_name' => $val->section->name,
                    ];
                }),
            ];
        })->groupBy(function ($item) {
            // Agrupa por section_name
            return $item['teachers'][0]['section_name'];
        })->map(function ($group) {
            // Construye la estructura final
            return [
                'section_name' => $group[0]['teachers'][0]['section_name'],
                'grades' => $group->map(function ($item) {
                    return [
                        'grade_id' => $item['grade_id'],
                        'grade_name' => $item['grade_name'],
                    ];
                })->toArray(),
            ];
        });

        return $data;
    }
}
