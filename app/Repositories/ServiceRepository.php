<?php

namespace App\Repositories;

use App\Models\Service;

class ServiceRepository extends BaseRepository
{
    public function __construct(Service $modelo)
    {
        parent::__construct($modelo);
    }

    public function list($request = [], $with = [], $select = ['*'])
    {
        $data = $this->model->select($select)->with($with)->where(function ($query) use ($request) {
            filterComponent($query, $request);

            if (! empty($request['title'])) {
                $query->where('title', 'like', '%' . $request['title'] . '%');
            }

            if (! empty($request['company_id'])) { 
                $query->where('company_id', $request['company_id']);
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
}
