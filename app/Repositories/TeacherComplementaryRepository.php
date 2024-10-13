<?php

namespace App\Repositories;

use App\Models\TeacherComplementary;

class TeacherComplementaryRepository extends BaseRepository
{
    public function __construct(TeacherComplementary $modelo)
    {
        parent::__construct($modelo);
    }

    public function list($request = [], $with = [], $select = ['*'])
    {
        $data = $this->model->select($select)->with($with)->where(function ($query) use ($request) {

            $query->whereHas('teacher', function ($q) {
                $q->where("name", "!=", "Materia");
                $q->where("last_name", "!=", "Pendiente");
            });

            if (! empty($request['grade_id'])) {
                $query->where('grade_id', $request['grade_id']);
            }
            if (! empty($request['section_id'])) {
                $query->where('section_id', $request['section_id']);
            }
            if (! empty($request['company_id'])) {
                $query->whereHas('teacher', function ($x) use ($request) {
                    $x->where('company_id', $request['company_id']);
                });
            }

            if (! empty($request['teacher_id'])) {
                $query->where('teacher_id', $request['teacher_id']);
            }
        })
            ->where(function ($query) use ($request) {
                if (! empty($request['searchQuery'])) {
                    $query->orWhere('name', 'like', '%' . $request['searchQuery'] . '%');
                }
            })
            ->orderBy($request['sort_field'] ?? 'id', $request['sort_direction'] ?? 'asc');

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
}
