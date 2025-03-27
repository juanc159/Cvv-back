<?php

namespace App\Http\Controllers;

use App\Repositories\PendingRegistrationFileRepository;
use App\Repositories\PendingRegistrationRepository;
use App\Traits\HttpResponseTrait;
use Illuminate\Http\Request;

class PendingRegistrationFilesController extends Controller
{
    use HttpResponseTrait;

    public function __construct(
        protected PendingRegistrationRepository $pendingRegistrationRepository,
        protected PendingRegistrationFileRepository $pendingRegistrationFileRepository,
    ) {}

    public function index($pending_registration_id)
    {
        return $this->runTransaction(function ()  use ($pending_registration_id) {

            $pendingRegistration = $this->pendingRegistrationRepository->find($pending_registration_id);
            $subjects = $pendingRegistration->uniqueSubjects();

            $pendingRegistration = [
                "id" => $pendingRegistration->id,
                "company_name" => $pendingRegistration->company?->name,
                "term_name" => $pendingRegistration->term?->name,
                "type_education_name" => $pendingRegistration->type_education?->name,
                "grade_name" => $pendingRegistration->grade?->name,
                "section_name" => $pendingRegistration->section_name,
            ];

            return [
                'code' => 200,
                'pendingRegistration' => $pendingRegistration,
                'subjects' => $subjects->values(),
            ];
        });
    }


    public function storeOrUpdate(Request $request)
    {
        return $this->runTransaction(function ()  use ($request) {


            // Validar la entrada
            $request->validate([
                'pending_registration_id' => 'required|uuid|exists:pending_registrations,id',
                'company_id' => 'required|exists:companies,id',
                'files_cant' => 'required|integer|min:0',
            ]);

            $company_id = $request->input('company_id');
            $pending_registration_id = $request->input('pending_registration_id');
            $fileCount = $request->input('files_cant'); // Obtén la cantidad de archivos

            // obtengo los ids de los archivos
            $arrayIds = [];
            for ($i = 0; $i < $fileCount; $i++) {
                $fileId = $request->input('file_id_' . $i);
                if ($fileId) {
                    $arrayIds[] = $fileId;
                }
            }


            $groupedData = [];
            for ($i = 0; $i < $fileCount; $i++) {
                // Agrupar los valores correspondientes por índice
                $groupedData[] = [
                    'id' => $request->input("file_id_{$i}"),
                    'subject_id' => $request->input("file_subject_id_{$i}"),
                    'name' => $request->input("file_name_{$i}"),
                ];
            }



            $this->pendingRegistrationFileRepository->deleteArray($arrayIds, $pending_registration_id);

            foreach ($groupedData as $key => $value) {
                $pendingRegistrationFile = $this->pendingRegistrationFileRepository->store([
                    'id' => $value['id'],
                    'pending_registration_id' => $pending_registration_id,
                    'subject_id' => $value['subject_id'],
                    'name' => $value['name'],
                ]);

                if ($request->file("file_file_{$key}")) {
                    $file = $request->file("file_file_{$key}");
                    $fileName = $file->getClientOriginalName();
                    $ruta = 'company_' . $company_id . '/pending_registrations/pending_registration_' . $pending_registration_id . '/files';
                    $path = $file->storeAs($ruta, $fileName, 'public');
                    $pendingRegistrationFile->path = $path;
                    $pendingRegistrationFile->save();
                }
            }
 
            // Devolver una respuesta con los archivos nuevos creados
            return [
                'code' => 200,
                'message' => 'Archivos guardados exitosamente',
            ];
        }, debug: false);
    }
 
}
