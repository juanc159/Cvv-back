<?php

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\LoadNoteMasiveController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\PassportAuthController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\WebSocketController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Events\TestEvent;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::get('/register', [PassportAuthController::class, 'register']);

Route::post('login', [PassportAuthController::class, 'login']);

Route::post('/password/email', [PassportAuthController::class, 'sendResetLink']);

Route::post('/password/reset', [PassportAuthController::class, 'passwordReset']);

Route::get('/teacher-downloadConsolidated/{id}', [TeacherController::class, 'downloadConsolidated']);
Route::get('/teacher-planningShow/{id?}', [TeacherController::class, 'planning'])->name('teacher.planning');

Route::get('/note-dataForm', [NoteController::class, 'dataForm']);

Route::post('/savefiles', [NoteController::class, 'savefiles']);

Route::get('/file/download', function (Request $request) {
    try {

        $ruta = public_path('/storage/' . $request->input('file'));
        // Verificar si el archivo existe
        if (! file_exists($ruta)) {
            return response()->json(['code' => 404, 'message' => 'Archivo no encontrado']);
        }

        // Descargar el archivo
        return response()->download($ruta);
    } catch (\Throwable $th) {
        return response()->json(['code' => 500, 'message' => 'Error al buscar los datos', 'error' => $th->getMessage()]);
    }
});






Route::get('/documentStudent/prosecutionInitialEducation', [DocumentController::class, 'prosecutionInitialEducation']);

Route::get('/documentStudent/certificateInitialEducation', [DocumentController::class, 'certificateInitialEducation']);

Route::get('/documentStudent/prosecutionPrimaryEducation', [DocumentController::class, 'prosecutionPrimaryEducation']);


// Route::post('/note-store', [NoteController::class, 'store']);

 
// // Tus rutas existentes...
// Route::post('/note-store', [LoadNoteMasiveController::class, 'process']);
// Route::get('/batch-status/{batchId}', [LoadNoteMasiveController::class, 'checkStatus']); // Para Polling
 

// // RUTA para Server-Sent Events (CORREGIDA)
// Route::get('/progress/{batchId}', [ProgressController::class, 'streamProgress']);


// Ruta principal para procesar archivos
Route::post('/note-store', [LoadNoteMasiveController::class, 'process']);
<<<<<<< HEAD

// Rutas WebSocket únicamente
Route::prefix('websocket')->group(function () {
    Route::get('/progress/{batchId}', [WebSocketController::class, 'getProgress']);
    Route::get('/connection-status', [WebSocketController::class, 'checkConnection']);
    Route::delete('/progress/{batchId}', [WebSocketController::class, 'cleanupProgress']);
});

 

Route::get('/test-broadcast', function () {
    event(new TestEvent('Hello from Laravel Reverb!'));
    return response()->json(['status' => 'Event fired!']);
});

Route::get('/simple-test', function () {
    \Illuminate\Support\Facades\Broadcast::channel('test-channel')->send([
        'event' => 'TestEvent',
        'data' => ['message' => 'Simple test message']
    ]);
    return 'Simple broadcast sent!';
});
=======
 
>>>>>>> origin/main
