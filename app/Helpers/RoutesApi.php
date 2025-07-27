<?php

namespace App\Helpers;

class RoutesApi
{
    // esto es para las apis que no requieran auth
    public const ROUTES_API = [
        'routes/api.php',
        'routes/pw.php',
        'routes/todo.php',
    ];

    // esto es para las apis que si requieran auth
    public const ROUTES_AUTH_API = [
        'routes/query.php',
        'routes/company.php',
        'routes/user.php',
        'routes/role.php',
        'routes/banner.php',
        'routes/subject.php',
        'routes/grade.php',
        'routes/service.php',
        'routes/student.php',
        'routes/teacher.php',
        'routes/note.php',
        'routes/dashboard.php',
        'routes/term.php',
        'routes/pendingRegistration.php',
        'routes/documents.php',
    ];
}
