<?php

namespace App\Http\Controllers;

use App\Helpers\Constants;
use App\Http\Requests\Student\StudentStoreRequest;
use App\Http\Resources\Student\StudentFormResource;
use App\Http\Resources\Student\StudentListResource;
use App\Repositories\SectionRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TypeEducationRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;

class StudentController extends Controller
{
    public function __construct(
        protected StudentRepository $studentRepository,
        protected TypeEducationRepository $typeEducationRepository,
        protected SectionRepository $sectionRepository,
        protected QueryController $queryController,
    ) {}

    public function list(Request $request)
    {
        try {
            $data = $this->studentRepository->list($request->all());
            $tableData = StudentListResource::collection($data);

            return [
                'code' => 200,
                'tableData' => $tableData,
                'lastPage' => $data->lastPage(),
                'totalData' => $data->total(),
                'totalPage' => $data->perPage(),
                'currentPage' => $data->currentPage(),
            ];
        } catch (Throwable $th) {
            return response()->json(['code' => 500, 'message' => 'Error Al Buscar Los Datos', $th->getMessage(), $th->getLine()]);
        }
    }

    public function create()
    {
        try {

            $selectInfiniteCountries = $this->queryController->selectInfiniteCountries(new Request([
                "idsAllowed" => [Constants::COUNTRY_ID_COLOMBIA, Constants::COUNTRY_ID_VENEZUELA]
            ]));

            $typeEducations = $this->typeEducationRepository->list(
                request: [
                    'typeData' => 'all',
                ],
                with: ['grades']
            )->map(function ($value) {
                return [
                    'value' => $value->id,
                    'title' => $value->name,
                    'grades' => $value->grades->map(function ($value2) {
                        return [
                            'value' => $value2->id,
                            'title' => $value2->name,
                        ];
                    }),
                ];
            });

            $sections = $this->sectionRepository->selectList();

            return response()->json([
                'code' => 200,
                'typeEducations' => $typeEducations,
                'sections' => $sections,
            ...$selectInfiniteCountries,

            ]);
        } catch (Throwable $th) {

            return response()->json(['code' => 500, $th->getMessage(), $th->getLine()]);
        }
    }

    public function store(StudentStoreRequest $request)
    {
        try {
            DB::beginTransaction();

            $post = $request->except(['photo']);

            $data = $this->studentRepository->store($post);

            if ($request->file('photo')) {
                $file = $request->file('photo');
                $photo = $file->store('company_'.$data->company_id.'/student/student_'.$data->id.$request->input('photo'), 'public');
                $data->photo = $photo;
                $data->save();
            }

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Estudiante agregado correctamente']);
        } catch (Throwable $th) {
            DB::rollBack();

            return response()->json([
                'code' => 500,
                'message' => 'Algo Ocurrio, Comunicate Con El Equipo De Desarrollo',
                'error' => $th->getMessage(),
                'line' => $th->getLine(),
            ], 500);
        }
    }

    public function edit($id)
    {
        try {
            $student = $this->studentRepository->find($id);
            $form = new StudentFormResource($student);

            $typeEducations = $this->typeEducationRepository->list(
                request: [
                    'typeData' => 'all',
                ],
                with: ['grades']
            )->map(function ($value) {
                return [
                    'value' => $value->id,
                    'title' => $value->name,
                    'grades' => $value->grades->map(function ($value2) {
                        return [
                            'value' => $value2->id,
                            'title' => $value2->name,
                        ];
                    }),
                ];
            });

            $sections = $this->sectionRepository->selectList();

            return response()->json([
                'code' => 200,
                'form' => $form,
                'typeEducations' => $typeEducations,
                'sections' => $sections,
            ]);
        } catch (Throwable $th) {

            return response()->json(['code' => 500, $th->getMessage(), $th->getLine()]);
        }
    }

    public function update(StudentStoreRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $post = $request->except(['photo']);

            $data = $this->studentRepository->store($post);

            if ($request->file('photo')) {
                $file = $request->file('photo');
                $photo = $file->store('company_'.$data->company_id.'/student/student_'.$data->id.$request->input('photo'), 'public');
                $data->photo = $photo;
                $data->save();
            }

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Estudiante modificado correctamente']);
        } catch (Throwable $th) {
            DB::rollBack();

            return response()->json([
                'code' => 500,
                'message' => 'Algo Ocurrio, Comunicate Con El Equipo De Desarrollo',
                'error' => $th->getMessage(),
                'line' => $th->getLine(),
            ], 500);
        }
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $student = $this->studentRepository->find($id);
            if ($student) {
                $student->delete();
                $msg = 'Registro eliminado correctamente';
            } else {
                $msg = 'El registro no existe';
            }
            DB::commit();

            return response()->json(['code' => 200, 'message' => $msg]);
        } catch (Throwable $th) {
            DB::rollBack();

            return response()->json([
                'code' => 500,
                'message' => $th->getMessage(),
                'error' => $th->getMessage(),
                'line' => $th->getLine(),
            ], 500);
        }
    }

    public function changeStatus(Request $request)
    {
        try {
            DB::beginTransaction();

            $model = $this->studentRepository->changeState($request->input('id'), strval($request->input('value')), $request->input('field'));

            ($model->is_active == 1) ? $msg = 'habilitada' : $msg = 'inhabilitada';

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Student '.$msg.' con éxito']);
        } catch (Throwable $th) {
            DB::rollback();

            return response()->json(['code' => 500, 'message' => $th->getMessage()]);
        }
    }

    public function resetPassword($id)
    {
        try {
            DB::beginTransaction();

            // Buscar al usuario por ID
            $model = $this->studentRepository->find($id);
            if (! $model) {
                return response()->json(['message' => 'Usuario no encontrado'], 404);
            }

            // Actualizar la contraseña
            $model->password = Hash::make($model->identity_document);
            $model->save();

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Contraseña reinicida con éxito']);
        } catch (Throwable $th) {
            DB::rollback();

            return response()->json(['code' => 500, 'message' => $th->getMessage()]);
        }
    }
}
