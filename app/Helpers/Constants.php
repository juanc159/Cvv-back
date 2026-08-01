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

    // Interruptor de las constancias/certificados de prosecución del portal del estudiante.
    // Solo se usan a fin de año escolar: el admin lo enciende en esa temporada y lo apaga después.
    public const ENABLE_PROSECUTION_DOCUMENTS = 'ENABLE_PROSECUTION_DOCUMENTS';



    /**
     * Regla de validación para las fotos de perfil (alumnos y docentes).
     *
     * Se valida por MIME real y no por la extensión: un archivo llamado ".jpg" puede
     * tener cualquier contenido y termina guardado en el disco público.
     */
    /** Se antepone 'nullable|' o 'required|' según el formulario. */
    public const RULE_PHOTO = 'file|mimetypes:image/jpeg,image/png,image/webp,image/gif|max:10240';

    public const MESSAGE_PHOTO = 'La foto debe ser una imagen (JPG, PNG, WEBP o GIF) de hasta 10 MB.';

    public const ERROR_MESSAGE_VALIDATION_BACK = 'Se evidencia algunos errores.';

    public const ERROR_MESSAGE_TRYCATCH = 'Algo Ocurrio, Comunicate Con El Equipo De Desarrollo.';

    public const REDIS_TTL = '315360000'; // 10 años en segundos
    
    public const DISK_FILES = 'public'; // sistema de archivos

    public const AVAILABLE_QUEUES_TO_IMPORTS_STUDENT_EXCEL = ['import_student_excel_1'];

    public const REDIS_PORT_TO_IMPORTS = "default";


}
