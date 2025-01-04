<?php

namespace App\Http\Controllers;

use App\Http\Requests\Project\ProjectStoreRequest;
use App\Http\Resources\Project\ProjectFormResource;
use App\Http\Resources\Project\ProjectListResource;
use App\Repositories\ProjectRepository;
use Illuminate\Support\Facades\DB;
use Throwable;
use Illuminate\Http\Request;


class ProjectController extends Controller
{
    public function __construct(
        protected ProjectRepository $projectRepository,
    ) {}

    public function list(Request $request)
    {
        try {
            $data = $this->projectRepository->list($request->all());
            $tableData = ProjectListResource::collection($data);

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

    public function store(ProjectStoreRequest $request)
    {
        try {
            DB::beginTransaction();

            $code = $this->projectRepository->generateCode();
            $link = url('/');

            $data = $this->projectRepository->store([
                'name' => $request->input("name"),
                'user_id' => $request->input("user_id"),
                'company_id' => $request->input("company_id"),
                'code' => $code,
                'link' => $link,
            ]);

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Proyecto creado correctamente']);
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
            $project = $this->projectRepository->find($id);
            $form = new ProjectFormResource($project);
 
            return response()->json([
                'code' => 200,
                'form' => $form, 

            ]);
        } catch (Throwable $th) {

            return response()->json(['code' => 500, $th->getMessage(), $th->getLine()]);
        }
    }

    public function update(ProjectStoreRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $this->projectRepository->store([
                'id' => $request->input("id"),
                'name' => $request->input("name"),
            ]);

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Proyecto actualizado correctamente']);
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
            $project = $this->projectRepository->find($id);
            if ($project) {
                $project->stickyNote->delete();
                $project->miniTextEditor->delete();
                $project->textCaption->delete();
                $project->drawing->delete();
                $project->delete();
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
}
