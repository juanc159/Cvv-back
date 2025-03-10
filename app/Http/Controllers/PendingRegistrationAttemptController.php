<?php

namespace App\Http\Controllers;

use App\Http\Requests\PendingRegistration\PendingRegistrationAttemptStoreRequest;
use App\Models\PendingRegistrationAttempt;
use App\Repositories\PendingRegistrationAttemptRepository;
use App\Traits\HttpResponseTrait;
use Illuminate\Support\Carbon;

class PendingRegistrationAttemptController extends Controller
{
    use HttpResponseTrait;

    public function __construct(
        protected PendingRegistrationAttemptRepository $pendingRegistrationAttemptRepository,
    ) {}

    public function index($pending_registration_id)
    {
        return $this->runTransaction(function ()  use ($pending_registration_id) {

            $attempts = $this->pendingRegistrationAttemptRepository->list([
                "pending_registration_id" => $pending_registration_id
            ], ['student', 'subject'])->map(function ($attempt) {
                return [
                    'id' => $attempt->id,
                    'student_id' => [
                        'value' => $attempt->student->id,
                        'title' => $attempt->student->full_name,
                    ],
                    'subject_id' => [
                        'value' => $attempt->subject->id,
                        'title' => $attempt->subject->name,
                    ],
                    'attempt_number' => $attempt->attempt_number, // Asegúrate de incluir este campo
                    'note' => $attempt->note,
                    'attempt_date' => Carbon::parse($attempt->attempt_date)->format("d-m-Y"),
                    'approved' => $attempt->approved,
                ];
            });

            return [
                'code' => 200,
                'data' => $attempts,
                'message' => 'Intentos recuperados exitosamente',
            ];
        });
    }


    public function store(PendingRegistrationAttemptStoreRequest $request)
    {
        return $this->runTransaction(function ()  use ($request) {

            $data = $request->validated();

            $pendingRegistrationId = $data['pending_registration_id'];
            $studentId = $data['student_id'];
            $subjectId = $data['subject_id'];
            $attemptNumber = $data['attempt_number'];
            $note = $data['note'];
            $attemptDate = $data['attempt_date'];

            // Verificar si ya existen 4 intentos para esta materia y alumno
            $existingAttempts = $this->pendingRegistrationAttemptRepository->countData([
                "pending_registration_id" => $pendingRegistrationId,
                "student_id" => $studentId,
                "subject_id" => $subjectId,
            ]);

            if ($existingAttempts >= 4) {
                return [
                    'code' => 422,
                    'message' => 'Se ha alcanzado el límite de 4 intentos para esta materia.',
                    'errors' => ['attempt_number' => ['Límite de intentos alcanzado.']],
                ];
            }

            $approved = $note >= 10;


            $attempt = $this->pendingRegistrationAttemptRepository->store([
                'pending_registration_id' => $pendingRegistrationId,
                'student_id' => $studentId,
                'subject_id' => $subjectId,
                'attempt_number' => $attemptNumber,
                'note' => $note,
                'attempt_date' => $attemptDate,
                'approved' => $approved,
            ]);

            return [
                'code' => 200,
                'data' => $attempt,
                'message' => 'Intento creado exitosamente',
            ];
        });
    }

    public function edit($id)
    {
        return $this->execute(function ()  use ($id) {
            $attempt = $this->pendingRegistrationAttemptRepository->find($id, ['student', 'subject']);

            return [
                'code' => 200,
                'data' => $attempt,
                'message' => 'Intento recuperado exitosamente',
            ];
        });
    }


    public function update(PendingRegistrationAttemptStoreRequest $request, $id)
    {
        return $this->runTransaction(function ()  use ($request, $id) {

            $data = $request->validated();

            $attempt = $this->pendingRegistrationAttemptRepository->store([
                'id' => $id,
                'note' => $data['note'],
                'attempt_date' => $data['attempt_date'],
                'approved' => $data['note'] >= 10,
            ]);


            return [
                'code' => 200,
                'data' => $attempt,
                'message' => 'Intento actualizado exitosamente',
            ];
        });
    }
}
