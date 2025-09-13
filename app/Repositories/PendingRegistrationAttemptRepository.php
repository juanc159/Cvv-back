<?php

namespace App\Repositories;

use App\Models\PendingRegistrationAttempt;

class PendingRegistrationAttemptRepository extends BaseRepository
{
    public function __construct(PendingRegistrationAttempt $modelo)
    {
        parent::__construct($modelo);
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

    public function list($request = [], $with = [], $select = ['*'])
    {
        $data = $this->model->select($select)->with($with)->where(function ($query) use ($request) {


            if (! empty($request['pending_registration_id'])) {
                $query->where('pending_registration_id', $request['pending_registration_id']);
            }  
 
        });
        $data = $data->get();

        return $data;
    }

    public function countData($request = [])
    {
        $data = $this->model->where(function ($query) use ($request) {
            if (! empty($request['pending_registration_id'])) {
                $query->where('pending_registration_id', $request['pending_registration_id']);
            }
            if (! empty($request['student_id'])) {
                $query->where('student_id', $request['student_id']);
            }
            if (! empty($request['subject_id'])) {
                $query->where('subject_id', $request['subject_id']);
            }
        })->count();

        return $data;
    }
}
