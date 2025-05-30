<?php

namespace App\Repositories;

use App\Models\TeacherPlanning;

class TeacherPlanningRepository extends BaseRepository
{
    public function __construct(TeacherPlanning $modelo)
    {
        parent::__construct($modelo);
    }

    public function list($request = [], $with = [], $select = ['*'])
    {
        $data = $this->model->select($select)->with($with)->where(function ($query) use ($request) {
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
        })
            ->where(function ($query) use ($request) {
                if (! empty($request['searchQuery'])) {
                    $query->orWhere('name', 'like', '%'.$request['searchQuery'].'%');
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

    public function deleteArray($arrayIds, $teacher_id)
    {
        $data = $this->model->whereNotIn('id', $arrayIds)->where('teacher_id', $teacher_id)->delete();

        return $data;
    }

    public function deleteAll($company_id)
    {
        $data = $this->model->whereHas('teacher', function ($query) use ($company_id) {
            $query->where('company_id', $company_id);
        })->delete();

        return $data;
    }
}
