<?php

namespace App\Http\Controllers;

use App\Events\ProjectBoardEvent;
use App\Models\Drawing;
use App\Models\Joinee;
use App\Models\MiniTextEditor;
use App\Models\Project;
use App\Models\StickyNote;
use App\Models\TextCaption;
use App\Repositories\ProjectRepository;
use Illuminate\Support\Facades\DB;
use Throwable;
use Illuminate\Http\Request;


class MiroController extends Controller
{
    public function __construct(
        protected ProjectRepository $projectRepository,
    ) {}

    public function addJoinees(Request $request)
    { 
        $projectCode = $request->input("project_code");
         $userId = $request->input("user_id");

        $project = Project::where('code', $projectCode)->first();

        if (!is_null($project)) {
            //check if user already added
            $joinee = Joinee::where('user_id', $userId)
                ->where('project_id', $project->id)
                ->first();

            if (is_null($joinee)) {
                Joinee::create([
                    'project_id' => $project->id,
                    'user_id' => $userId
                ]);
                ProjectBoardEvent::dispatch($projectCode);

                return response([
                    'message' => 'Usuario uniendose al proyecto',
                    'status' => true,
                    "project" => $project
                ]);
            } else {

                ProjectBoardEvent::dispatch($projectCode);

                return response([
                    'message' => 'usuario ya se encuentra en el proyecto',
                    'status' => true,
                    "project" => $project

                ]);
            }
        } else {

            return response(['message' => 'proyecto no encontrado', 'status' => false]);
        }
    }

    public function details(Request $request)
    {
        try {

            $project = $this->projectRepository->searchOne($request->all());

            return response([
                'project' => $project,
            ], 200);


            return response()->json(['code' => 200, 'project' => $project]);
        } catch (Throwable $th) {

            return response()->json([
                'code' => 500,
                'message' => $th->getMessage(),
                'error' => $th->getMessage(),
                'line' => $th->getLine(),
            ], 500);
        }
    }
    public function project_boards(Request $request)
    {
        try {
            $projectId = $request->input("project_id");
            $userId = $request->input("user_id");

            $miniTextEditor = null;
            $stickyNote = null;
            $textCaption = null;
            $drawing = null;

            $project = $this->projectRepository->find($projectId);


            
            // if ($userId === $project->user_id) {
                $miniTextEditor = MiniTextEditor::where('project_id', $projectId)
                    ->first();
                $stickyNote = StickyNote::where('project_id', $projectId)
                    ->first();
                $textCaption = TextCaption::where('project_id', $projectId)
                    ->first();
                $drawing = Drawing::where('project_id', $projectId)
                    ->first();
            // }


            return response([
                'miniTextEditor' => $miniTextEditor,
                'stickyNote'    => $stickyNote,
                'textCaption'   => $textCaption,
                'drawing'       => $drawing,
            ], 200);


            return response()->json(['code' => 200, 'project' => $project]);
        } catch (Throwable $th) {

            return response()->json([
                'code' => 500,
                'message' => $th->getMessage(),
                'error' => $th->getMessage(),
                'line' => $th->getLine(),
            ], 500);
        }
    }


    public function createOrUpdateMiniTextEditor(Request $request)
    {
        try {
            DB::beginTransaction();

            $json = json_encode($request->input('data'));
            $miniTextEditor = MiniTextEditor::updateOrCreate(
                ['project_id' => $request->input('project_id')],
                ['data' =>  $json]
            );

            DB::commit();

            return response()->json(['code' => 200]);
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

    public function createOrUpdateStickyNote(Request $request)
    {
        try {
            DB::beginTransaction();

            $json = json_encode($request->input('data'));
            $stickyNote = StickyNote::updateOrCreate(
                ['project_id' => $request->input('project_id')],
                ['data' =>  $json]
            );

            DB::commit();

            return response()->json(['code' => 200]);
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

    public function createOrUpdateTextCaption(Request $request)
    {
        try {
            DB::beginTransaction();

            $json = json_encode($request->input('data'));
            $textCaption = TextCaption::updateOrCreate(
                ['project_id' => $request->input('project_id')],
                ['data' =>  $json]
            );

            DB::commit();

            return response()->json(['code' => 200]);
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

    public function createOrUpdateDrawing(Request $request)
    {
        try {
            DB::beginTransaction();

            $json = json_encode($request->input('data'));
            $drawing = Drawing::updateOrCreate(
                ['project_id' => $request->input('project_id')],
                ['data' =>  $json]
            );

            DB::commit();

            return response()->json(['code' => 200]);
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
}
