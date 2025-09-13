<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notas del Estudiante</title>
</head>
<style>
    @page {
        margin: 0cm 0cm;
    }

    * {
        font-family: 'Roboto', sans-serif !important;

    }

    body {
        margin-top: 2.5cm;
        margin-left: 0cm;
        margin-right: 0cm;
        margin-bottom: 0.2cm;

    }

    header {
        position: fixed;
        top: 0cm;
        left: 0cm;
        right: 0cm;
        height: 3cm;
    }

    footer {
        position: fixed;
        bottom: 0cm;
        left: 0cm;
        right: 0cm;
        height: 2.2cm;
    }

    table {
        font-size: 12px;
        width: 100%;
        border-spacing: 5px;
    }

    td {
        border-radius: 5px;
        padding: 5px;
    }

    .text-media {
        font-size: 14px;
    }

    /* Estilo para la tabla de notas */
    .notes-table {
        width: 500px;
        margin: 0 auto;
    }

    .notes-table th,
    .notes-table td {
        border: 1px solid #777;
        padding: 8px;
        text-align: center;
    }

    .pending-table {
        width: 90%;
        margin: 30px auto;
        border-collapse: collapse;
        background-color: #fff8f0;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        font-size: 13px;
    }

    .pending-table th {
        background-color: #1a75ff;
        color: white;
        padding: 10px;
        text-align: center;
        border: 1px solid #ddd;
    }

    .pending-table td {
        border: 1px solid #ddd;
        padding: 8px;
        vertical-align: top;
    }

    .moments-table {
        width: 100%;
        margin-top: 8px;
        border-collapse: collapse;
    }

    .moments-table th {
        background-color: #ffe0cc;
        color: #333;
        padding: 6px;
        font-size: 12px;
        border: 1px solid #ccc;
    }

    .moments-table td {
        border: 1px solid #ccc;
        padding: 5px;
        font-size: 12px;
        text-align: center;
    }

    .pending-note {
        background-color: #ffe6e6 !important;
        color: #d60000;
        font-weight: bold;
    }

    .approved-note {
        background-color: #e6ffe6 !important;
        color: #006400;
        font-weight: bold;
    }

    .subject-title {
        background-color: #cce6ff;
        padding: 6px 10px;
        font-weight: bold;
        font-size: 13px;
        border-left: 4px solid #1a75ff;
        border-bottom: 1px solid #ccc;
    }
</style>

<body>
    <!-- IMAGEN DE ENCABEZADO -->
    <header>
        <img src="{{ public_path('img/header.png') }}" style="width: 100%;">
    </header>

    <footer>
        <!-- Puedes agregar un pie de página si lo deseas -->
    </footer>

    <main>
        <div
            style="text-align: center; background-image: url({{ public_path('img/background.jpg') }}); background-repeat: no-repeat; background-position: center; background-size: 50% auto;">
            <table>
                <tr>
                    <td colspan="2" style="text-align: center">
                        <b>
                            <label style="color: #000 !important; font-size: 20px;">Calificaciones Acumulativas <br>
                                {{ $data['student']['type_education']['name'] }}</label>
                        </b>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; padding-right: 20px">
                        <label class="text-media">
                            <span style="color: #8e8e8e">Año:</span>
                            <span>{{ $data['student']['grade']['name'] }}</span>
                        </label>
                    </td>
                    <td>
                        <label class="text-media">
                            <span style="color: #8e8e8e">SECCIÓN:</span>
                            <span>{{ $data['student']['section']['name'] }}</span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; padding-right: 10px">
                        <label class="text-media">
                            <span style="color: #8e8e8e">NOMBRES Y APELLIDOS DEL ESTUDIANTE:</span>
                        </label>
                    </td>
                    <td>
                        <label class="text-media">
                            <span style="color: black;">{{ $data['student']['full_name'] }}</span>
                        </label>
                    </td>
                </tr>
            </table>

            <!-- Tabla de notas -->
            <table class="notes-table">
                <tr>
                    <th>MATERIA </th>
                    @for ($i = 1; $i <= $data['student']['type_education']['cantNotes']; $i++)
                        @php
                            $isNoteSelected = false;
                            foreach ($data['student']['type_education']->note_selections as $selection) {
                                if ($selection->note_number == $i && $selection->is_selected) {
                                    $isNoteSelected = true;
                                    break;
                                }
                            }
                        @endphp
                        @if ($isNoteSelected)
                            <th>NOTA {{ $i }}</th>
                        @endif
                    @endfor
                </tr>
                @foreach ($data['filteredNotes'] as $key => $nota)
                    <tr>
                        <td style="text-align: center; font-size: 15px;"><span>{{ $nota['subject']['name'] }}</span>
                        </td>
                        @php
                            $valores = json_decode($nota['json'], true);
                            $noteSelections = $data['student']['type_education']->note_selections;
                        @endphp
                        @foreach ($valores as $key => $val)
                            @php
                                $isNoteSelected = false;
                                foreach ($noteSelections as $selection) {
                                    if ($selection->note_number == $key && $selection->is_selected) {
                                        $isNoteSelected = true;
                                        break;
                                    }
                                }
                            @endphp
                            @if ($isNoteSelected)
                                <td
                                    style="border: 1px solid rgb(119, 119, 119); padding-left: 8px; padding-right: 8px; padding: 4px; text-align: center;">
                                    <span>{{ $val }}</span>
                                </td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            </table>

            <!-- Pie de página del contenido -->
            <div style="width: 100%; text-align: center; margin-top: 10px;">
                <span style="font-size: 12px; font-style: italic;">U.E. COLEGIO VIRGEN DEL VALLE ¡DÓNDE LA EDUCACIÓN DEL
                    FUTURO ES HOY!</span>
            </div>
            <table style="width: 500px;" align="center">
                <tr>
                    <td align="center">
                        <img src="{{ public_path('img/firma.png') }}" style="max-width: 150px !important;">
                    </td>
                </tr>
                <tr>
                    <td align="center">
                        <span>{{ $data['date'] }}</span>
                    </td>
                </tr>
            </table>


            <!-- Nueva sección: Materias pendientes -->
            @if ($data['pendingAttempts']->isNotEmpty())
                <div style="page-break-before: always; margin-top: 30px;">
                    <h3
                        style="
            text-align: center;
            color: #1a75ff;
            font-size: 16px;
            background-color: #e6f2ff;
            padding: 10px 20px;
            border-radius: 8px;
            display: inline-block;
            margin: 0 auto;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.05);">
                        Reporte de Materias Pendientes
                    </h3>
                    <table class="pending-table">
                        @foreach ($data['pendingAttempts'] as $subjectId => $attempts)
                            <tr>
                                <td>
                                    <div class="subject-title">{{ $attempts->first()->subject->name }}</div>
                                    <<table class="moments-table">
                                        <thead>
                                            <tr>
                                                <th>Momento</th>
                                                <th>Nota</th>
                                                <th>Fecha</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($attempts as $attempt)
                                                <tr>
                                                    <td>{{ $attempt->attempt_number }}</td>
                                                    <td
                                                        class="{{ $attempt->note !== null ? ($attempt->note >= 10 ? 'approved-note' : 'pending-note') : 'pending-note' }}">
                                                        {{ $attempt->note === null ? 'Pendiente' : number_format($attempt->note, 0) }}
                                                    </td>
                                                    <td>{{ $attempt->attempt_date ? $attempt->attempt_date->format('d-m-Y') : 'No programada' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                    </table>
                    </td>
                    </tr>
            @endforeach
            </table>
        </div>
        @endif

        </div>
    </main>

</body>

</html>
