<?php

use App\Models\Student;
use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/linkstorage', function () {
    $tenants = Tenant::get();
    $data = [];
    $data[public_path('storage')] = storage_path('app/public');

    foreach ($tenants as $tenant) {
        $data[public_path('storage_' . $tenant['id'])] = storage_path('storage_' . $tenant['id'] . '/app/public');
    }

    Config::set('filesystems.links', $data);
    Config::get('filesystems.links', 'public');

    return Artisan::call('storage:link');
});



Route::get('/images', function () {
    // Ruta completa a la carpeta 'student' dentro de 'public'
    $studentFolder = public_path('student');

    // Verificamos si la carpeta 'student' existe
    if (File::exists($studentFolder)) {
        // Obtener solo las subcarpetas dentro de 'student'
        $directories = File::directories($studentFolder);

        // Filtrar las carpetas que comienzan con 'student_' seguido de un número
        $filteredDirectories = array_filter($directories, function ($directory) {
            return preg_match('/student_\d+/', basename($directory));
        });

        // Iteramos sobre cada carpeta filtrada
        foreach ($filteredDirectories as $directory) {
            // Obtener el ID de la carpeta (el número después de 'student_')
            $folderName = basename($directory);
            $studentId = str_replace('student_', '', $folderName); // Removemos 'student_' para obtener el ID

            // Obtener todos los archivos dentro de la carpeta
            $files = File::files($directory);

            if (count($files) > 0) {
                // Obtener el primer archivo de la carpeta
                $firstFile = $files[0]; // El primer archivo en el array

                // Crear la ruta accesible dentro de 'storage'
                // La estructura será: company_{id}/student/student_{id}/{nombre_archivo}
                $relativePath = 'company_1/student/' . $folderName . '/' . basename($firstFile);

                // Aquí generamos la URL completa
                $fileUrl = url('storage/' . $relativePath);

                // Actualizamos el campo 'photo' del estudiante con la URL completa
                $student = Student::find($studentId);
                if ($student) {
                    $student->photo = $fileUrl;  // Guardamos la URL completa en el campo 'photo'
                    $student->save();
                }
            }
        }

        return response()->json(['success' => 'Fotos actualizadas correctamente']);
    } else {
        // Si la carpeta no existe, retornamos un mensaje de error
        return response()->json(['error' => 'La carpeta no existe.'], 404);
    }
});
