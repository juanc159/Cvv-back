<?php

namespace App\Helpers;

class Constants
{
    // Agrega más constantes según sea necesario

    public const COMPANY_UUID = '23a0eb68-95b6-49c0-9ad3-0f60627bf220';

    public const ROLE_SUPERADMIN_UUID = '21626ff9-4940-4143-879a-0f75b46eadb7';

    public const COUNTRY_ID_COLOMBIA = '48'; // Colombia

    public const COUNTRY_ID_VENEZUELA = '239'; // Venezuela

    public const ITEMS_PER_PAGE = '10'; // PARA LA PAGINACIONES

    public const INITIAL_EDUCATION_UUID = '1';

    public const PRIMARY_EDUCATION_UUID = '2';

    public const GENERAL_SECONDARY_EDUCATION_UUID = '3';

    public const TEACHERS_UUID = '3';

    public const MANAGERS_UUID = '1';

    public const COORDINATORS_UUID = '2';

    public const SPECIALISTS_UUID = '4';

    public const INITIAL_EDUCATION = '1';

    public const PRIMARY_EDUCATION = '2';

    public const HIGH_SCHOOL_EDUCATION = '3';

    public const BLOCK_PAYROLL_UPLOAD = 'BLOCK_PAYROLL_UPLOAD';



    public const ERROR_MESSAGE_VALIDATION_BACK = 'Se evidencia algunos errores.';

    public const ERROR_MESSAGE_TRYCATCH = 'Algo Ocurrio, Comunicate Con El Equipo De Desarrollo.';

    public const REDIS_TTL = '315360000'; // 10 años en segundos
}
