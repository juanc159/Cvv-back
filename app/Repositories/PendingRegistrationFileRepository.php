<?php

namespace App\Repositories;

use App\Models\PendingRegistrationFile; 

class PendingRegistrationFileRepository extends BaseRepository
{
    public function __construct(PendingRegistrationFile $modelo)
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
 

    public function deleteArray($arrayIds, $pending_registration_id)
    {
        $data = $this->model->whereNotIn('id', $arrayIds)->where('pending_registration_id', $pending_registration_id)->delete();

        return $data;
    }
}
