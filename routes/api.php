<?php

use App\Http\Controllers\NoteController;
use App\Http\Controllers\PassportAuthController;
use App\Http\Controllers\TeacherController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::get('/register', [PassportAuthController::class, 'register']);
Route::post('login', [PassportAuthController::class, 'login']);

Route::get('/teacher-downloadConsolidated/{id}', [TeacherController::class, 'downloadConsolidated']);
Route::get('/teacher-planningShow/{id?}', [TeacherController::class, 'planning'])->name('teacher.planning');

Route::get('/note-dataForm', [NoteController::class, 'dataForm']);
Route::post('/note-store', [NoteController::class, 'store']);




Route::post('/savefiles', [NoteController::class, 'savefiles']);


Route::get('/file/download', function (Request $request) {
    try {

        $ruta = public_path('/storage/' . $request->input("file"));
        // Verificar si el archivo existe
        if (!file_exists($ruta)) {
            return response()->json(['code' => 404, 'message' => 'Archivo no encontrado']);
        }

        // Descargar el archivo
        return response()->download($ruta);
    } catch (\Throwable $th) {
        return response()->json(['code' => 500, 'message' => 'Error al buscar los datos', 'error' => $th->getMessage()]);
    }
});
