<?php

namespace App\Repositories;

use App\Models\PendingRegistrationSubject;

class PendingRegistrationSubjectRepository extends BaseRepository
{
    public function __construct(PendingRegistrationSubject $modelo)
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


    public function exists($pendingRegistrationStudentsId, $subjectId)
    {
        return $this->model->where('pending_registration_student_id', $pendingRegistrationStudentsId)
            ->where('subject_id', $subjectId)
            ->exists();
    }

    public function addSubjects($pendingRegistrationStudentsId, array $subjects)
    {
        foreach ($subjects as $subjectId) {
            if (!$this->exists($pendingRegistrationStudentsId, $subjectId)) {
                $this->store([
                    'pending_registration_student_id' => $pendingRegistrationStudentsId,
                    'subject_id' => $subjectId,
                ]);
            }
        }
    }



    public function syncSubjects($pendingRegistrationStudentId, $newSubjects)
    {

        // Obtener las materias actuales del estudiante
        $currentSubjects = $this->model::where('pending_registration_student_id', $pendingRegistrationStudentId)
            ->pluck('subject_id')
            ->toArray();

        // Convertir los nuevos subjects a un array (por si acaso)
        $newSubjects = (array) $newSubjects;

        // Encontrar materias a eliminar (las que están en current pero no en new)
        $subjectsToDelete = array_diff($currentSubjects, $newSubjects);
        if (!empty($subjectsToDelete)) {
            $this->model::where('pending_registration_student_id', $pendingRegistrationStudentId)
                ->whereIn('subject_id', $subjectsToDelete)
                ->delete();
        }

        // Encontrar materias a agregar (las que están en new pero no en current)
        $subjectsToAdd = array_diff($newSubjects, $currentSubjects);

        // Agregar las nuevas materias
        $this->addSubjects($pendingRegistrationStudentId, $subjectsToAdd);
    }

    public function deleteByPendingRegistrationId($pendingRegistrationId)
    {
        return $this->model::whereIn('pending_registration_student_id', function ($query) use ($pendingRegistrationId) {
            $query->select('id')
                ->from('pending_registration_students')
                ->where('pending_registration_id', $pendingRegistrationId);
        })->delete();
    }
}
