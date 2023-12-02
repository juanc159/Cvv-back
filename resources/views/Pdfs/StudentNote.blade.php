<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Calificaciones Acumulativas</title>
    <style>
        /* Estilos generales */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f7f7f7;
        }

        /* Estilos para el encabezado */
        .header {
            background-color: #3498db;
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }

        /* Estilos para el cuerpo */
        .body {
            padding: 20px;
        }

        /* Estilos para la tabla */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        th {
            background-color: #f1c40f;
            color: white;
        }

        /* Estilos para el pie de página */
        .footer {
            background-color: #3498db;
            color: white;
            padding: 20px;
            text-align: center;
            margin-top: 20px;
        }


    </style>
</head>

<body>
    <div class="body">
        <div class="header">
            <h1>Calificaciones Acumulativas - {{ $data['student']['typeEducation']['name'] }}</h1>
        </div>

        <div>
            <strong>AÑO: {{ $data['student']['grade']['name'] }}</strong><br>
            <strong>SECCIÓN: {{ $data['student']['section']['name'] }}</strong><br>
            <strong>NOMBRES Y APELLIDOS DEL ESTUDIANTE: {{ $data['student']['full_name'] }}</strong><br>
        </div>

        <div>
            <table>
                <thead>
                    <tr>
                        <th>Materia</th>
                        <th>Nota 1</th>
                        <th>Nota 2</th>
                        <th>Nota 3</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data['student']['notes'] as $note)
                        <tr>
                            <td>{{ $note['subject']['name'] }}</td>
                            <td>{{ $note['value1'] ?? "-" }}</td>
                            <td>{{ $note['value2'] ?? "-" }}</td>
                            <td>{{ $note['value3'] ?? "-" }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="footer">
            <h2>{{ $data['student']['company']['slogan'] }}</h2>
        </div>
    </div>
</body>

</html>
