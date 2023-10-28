<?php

namespace App\Repositories;

use App\Models\TypeDetail;

class TypeDetailRepository extends BaseRepository
{
    public function __construct(TypeDetail $modelo)
    {
        parent::__construct($modelo);
    }

    public function list($request = [], $with = [], $select = ['*'], $idsAllowed = [], $idsNotAllowed = [])
    {
        $data = $this->model->select($select)
            ->with($with)
            ->where(function ($query) use ($request, $idsAllowed, $idsNotAllowed) {
                if (!empty($request['name'])) {
                    $query->where('name', 'like', '%' . $request['name'] . '%');
                }
            })
            ->where(function ($query) use ($request) {
                if (!empty($request['searchQuery'])) {
                    $query->orWhere('name', 'like', '%' . $request['searchQuery'] . '%');
                }
            });
        if (empty($request['typeData'])) {
            $data = $data->paginate($request['perPage'] ?? 10);
        } else {
            $data = $data->get();
        }

        return $data;
    }

    public function store(array $request)
    {
        $request = $this->clearNull($request);

        if (!empty($request['id'])) {
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

    public function selectList($request = [])
    {
        $data = $this->model->where(function($request){

        })->get()->map(function ($value) {
            return [
                "value" => $value->id,
                "title" => $value->name,
            ];
        });
        return $data;
    }
}
