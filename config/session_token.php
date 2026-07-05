<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Duración de la sesión (token Passport) por tipo de usuario
    |--------------------------------------------------------------------------
    |
    | Caducidad ABSOLUTA en horas, contada desde el login. Al expirar, el
    | usuario recibe 401 y el frontend lo manda a iniciar sesión de nuevo.
    | Los alumnos usan una sesión corta; el personal, una jornada.
    | Ajustable sin tocar código vía variables de entorno.
    |
    */

    'ttl_hours' => [
        'student' => (int) env('SESSION_TTL_STUDENT_HOURS', 2),
        'teacher' => (int) env('SESSION_TTL_TEACHER_HOURS', 8),
        'admin' => (int) env('SESSION_TTL_ADMIN_HOURS', 8),
    ],

    // Fallback para cualquier type_user desconocido o nulo.
    'default_hours' => (int) env('SESSION_TTL_DEFAULT_HOURS', 2),

    // Rol Super Administrador: único autorizado a cerrar todas las sesiones.
    'super_admin_role_id' => env('SUPER_ADMIN_ROLE_ID', '21626ff9-4940-4143-879a-0f75b46eadb7'),

];
