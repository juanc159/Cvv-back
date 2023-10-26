<?php

namespace App\Repositories\Querys;

use App\Models\City;
use App\Repositories\BaseRepository;

class CityRepository extends BaseRepository
{
    public function __construct(City $modelo)
    {
        parent::__construct($modelo);
    }

    public function list($request = [], $with = [], $select = ['*'], $idsAllowed = [])
    {
        $data = $this->model->select($select)
            ->with($with)
            ->where(function ($query) use ($request, $idsAllowed) {
                if (! empty($request['name'])) {
                    $query->where('id', 'like', '%'.$request['name'].'%');
                }
                if (count($idsAllowed) > 0) {
                    $query->whereIn('id', $idsAllowed);
                }
                if (! empty($request['idsAllowed']) && count($request['idsAllowed']) > 0) {
                    $query->whereIn('id', $request['idsAllowed']);
                }
            })
            ->where(function ($query) use ($request) {
                if (! empty($request['searchQuery'])) {
                    $query->orWhere('name', 'like', '%'.$request['searchQuery'].'%');
                }
            });
        if (empty($request['typeData'])) {
            $data = $data->paginate($request['perPage'] ?? 12);
        } else {
            $data = $data->get();
        }

        return $data;
    }

    public function selectList($department_id)
    {
        return $this->model->where('department_id', $department_id)->get()->map(function ($value) {
            return [
                'value' => $value->id,
                'title' => $value->name,
            ];
        });
    }
}
