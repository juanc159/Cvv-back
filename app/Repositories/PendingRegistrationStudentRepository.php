<?php

namespace App\Repositories;

use App\Models\PendingRegistrationStudent;

class PendingRegistrationStudentRepository extends BaseRepository
{
    public function __construct(PendingRegistrationStudent $modelo)
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

    public function getStudentsWithSubjectsByPendingRegistrationId($pendingRegistrationId)
    {
        return $this->model::with(["student:id,full_name,identity_document"])->select(["id", "pending_registration_id", "student_id"])->where('pending_registration_id', $pendingRegistrationId)
            ->with(['subjects' => function ($query) {
                $query->select('pending_registration_subjects.subject_id', 'pending_registration_subjects.pending_registration_student_id');
            }])
            ->get();
    }

    public function findByPendingRegistrationAndStudent($pendingRegistrationId, $studentId)
    {
        return $this->model::where('pending_registration_id', $pendingRegistrationId)
            ->where('student_id', $studentId)
            ->first();
    }

    
}
