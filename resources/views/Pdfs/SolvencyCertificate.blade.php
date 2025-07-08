<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificado de Solvencia</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 12px;

        }

        /* Encabezado con imagen */
        header {
            margin-bottom: 1px;
        }

        header img {
            width: 100%;
            height: auto;
        }

        /* Contenedor del código de solvencia a la derecha */
        .codigo-container {
            text-align: right;
            /* Alinear a la derecha */
            margin-bottom: 15px;
        }

        .solvencia-code {
            border: 1px solid #000;
            padding: 5px 15px;
            font-weight: bold;
            display: inline-block;
            /* Display inline para margen */
        }

        .content {
            margin: 20px 0;
            line-height: 1.5;
            text-align: justify;
        }

        /* Nombre del estudiante más grande */
        .student-name {
            font-weight: bold;
            text-align: center;
            font-size: 20px;
            /* Aumentar el tamaño */
            margin: 10px 0;
            text-transform: uppercase;
        }

        /* Sangría en los párrafos */
        .indent {
            text-indent: 30px;
        }

        /* Sección de nota y firma */
        .note-section {
            margin-top: 10px;
        }

        /* Tabla para dividir la sección de nota y firma */
        .note-table {
            width: 100%;
            table-layout: fixed;
            /* Distribuye el ancho de las columnas */
        }

        .note-cell {
            width: 50%;
            /* Cada celda ocupa 50% del ancho */
            vertical-align: top;
            /* Alinea el contenido hacia arriba */
        }

        .note {
            font-size: 12px;
            font-weight: bold;
            text-align: justify;
        }

        .firma {
            text-align: center;
        }

        .firma img {
            width: 120px;
            /* Tamaño de la imagen de la firma */
            height: auto;
        }

        /* Footer */
        .footer {
            margin-top: 0;
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 5px;
            line-height: 1;
            /* Elimina el interlineado */
            font-size: 10px;
        }
    </style>
</head>

<body>

    <!-- IMAGEN DE ENCABEZADO -->
    <header>
        <img src="{{ public_path('img/header.png') }}" style="width: 100%;">
    </header>

    <!-- Código de solvencia a la derecha -->
    <div class="codigo-container">
        <div class="solvencia-code">
            CÓDIGO DE SOLVENCIA:
            <strong>{{ $data['solvencyCode'] }}</strong>
        </div>
    </div>

    <!-- Texto principal con sangría -->
    <div class="content indent">
        Quien suscribe, Arq. JESSIKA MORENO, del departamento de Administración de la U.E. Colegio Virgen del Valle, por
        medio de la presente hace constar que el estudiante:
    </div>

    <!-- Nombre del estudiante -->
    <div class="student-name">
        {{ strtoupper($data['student']['full_name']) }}
    </div>

    <!-- Información del estudiante con sangría -->
    <div class="content indent">
        Portador(a) {{ $data['student']['type_document_name'] }} Nro
        {{ $data['student']['identity_document'] }}, estudiante del Grado -Año - Sección:
        <strong>{{ $data['student']['grade_name'] }}</strong>, se encuentra
        <strong>SOLVENTE</strong> en el año escolar {{ $data['student']['school_year'] }}.
    </div>

    <!-- Sección de nota y firma -->
    <div class="note-section">
        <table class="note-table">
            <tr>
                <td class="note-cell">
                    <div class="note">
                        NOTA: PARA REALIZAR LA INSCRIPCIÓN DEL AÑO ESCOLAR {{ $data['next_school_year'] }}, ES NECESARIO
                        EL
                        CÓDIGO DE
                        SOLVENCIA
                        PARA EL ACCESO AL PROCESO.
                    </div>
                </td>
                <td class="note-cell">
                    <div class="firma">
                        <img src="{{ public_path('img/firmaJesika.png') }}" alt="Firma">
                        <img src="{{ public_path('img/soloSello.png') }}" alt="Firma">
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        ¡Dónde la Educación del futuro es hoy!<br><br>
        Avenida principal de Arjona. Carrera 2. Edo. Táchira. Teléfono 02763946955<br>
        Página WEB https://colegiovirgendelvalle.blogspot.com, E-mail: colegiovirgendelvalle.adm@gmail.com
    </div>
</body>

</html>
