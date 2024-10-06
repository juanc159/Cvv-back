<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserRepository extends BaseRepository
{
    public function __construct(User $modelo)
    {
        parent::__construct($modelo);
    }

    public function list($request = [], $with = [], $select = ['*'], $idsAllowed = [], $idsNotAllowed = [])
    {
        $data = $this->model->select($select)
            ->with($with)
            ->where(function ($query) use ($request, $idsAllowed, $idsNotAllowed) {
                filterComponent($query, $request);

                if (! empty($request['name'])) {
                    $query->where('name', 'like', '%' . $request['name'] . '%');
                }

                if (! empty($request['company_id'])) { 
                    $query->where('company_id', $request['company_id']);
                } 
                //idsAllowed
                if (count($idsAllowed) > 0) {
                    $query->whereIn('id', $idsAllowed);
                }
                if (! empty($request['idsAllowed']) && count($request['idsAllowed']) > 0) {
                    $query->whereIn('id', $request['idsAllowed']);
                }

                //idsNotAllowed
                if (count($idsNotAllowed) > 0) {
                    $query->whereNotIn('id', $idsNotAllowed);
                }
                if (! empty($request['idsNotAllowed']) && count($request['idsNotAllowed']) > 0) {
                    $query->whereNotIn('id', $request['idsNotAllowed']);
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

    public function store(array $request)
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

        // if (! empty($data['password'])) {
        //     $data['password'] = Hash::make($data['password']);
        // } else {
        //     unset($data['password']);
        // }

        $data->save();

        return $data;
    }

    public function register($request)
    {
        $data = $this->model;

        foreach ($request as $key => $value) {
            $data[$key] = $request[$key];
        }

        $data->save();

        return $data;
    }

    public function findByEmail($email)
    {
        return $this->model::where('email', $email)->first();
    }

    public function searchOne($request = [], $with = [], $select = ['*'])
    {
        $data = $this->model->select($select)->with($with)->where(function ($query) use ($request) {
            if (! empty($request['teacher_id'])) {
                $query->where('teacher_id', $request['teacher_id']);
            }
        });

        $data = $data->first();

        return $data;
    }
}
