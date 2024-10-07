<?php

namespace App\Http\Controllers;

use App\Http\Resources\Student\StudentListResource;
use App\Models\Student;
use App\Repositories\StudentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;

class StudentController extends Controller
{
    private $studentRepository;

    public function __construct(
        StudentRepository $studentRepository,
    ) {
        $this->studentRepository = $studentRepository;
    }

    public function list(Request $request)
    {
        $data = $this->studentRepository->list($request->all());
        $students = StudentListResource::collection($data);

        return [
            'tableData' => $students,
            'lastPage' => $data->lastPage(),
            'totalData' => $data->total(),
            'totalPage' => $data->perPage(),
            'currentPage' => $data->currentPage(),
        ];
    }



    public function resetPassword($id)
    {
        try {
            DB::beginTransaction();

            // Buscar al usuario por ID
            $user = Student::find($id);
            if (!$user) {
                return response()->json(['message' => 'Usuario no encontrado'], 404);
            }

            // Actualizar la contraseña
            $user->password = Hash::make($user->identity_document);
            $user->save();

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Contraseña reinicida con éxito']);
        } catch (Throwable $th) {
            DB::rollback();

            return response()->json(['code' => 500, 'message' => $th->getMessage()]);
        }
    }
}
