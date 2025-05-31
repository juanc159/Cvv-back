<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Constancia de Prosecución - Educación Primaria</title>
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
        margin-bottom: 3cm;
        min-height: calc(100% - 5.5cm);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        background-image: url({{ public_path('img/background.jpg') }});
        background-repeat: no-repeat;
        background-position: center center;
        background-size: 70% auto;
        background-attachment: fixed;
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
        bottom: 0.5cm;
        left: 0cm;
        right: 0cm;
        height: 2cm;
        text-align: center;
        font-size: 12px;
        line-height: 1.2;
        color: #333;
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

    .indented-text {
        text-indent: 2cm;
        text-align: justify;
        width: 80%;
        margin-left: auto;
        margin-right: auto;
        font-size: 16px;
        line-height: 1.5;
        margin-top: 0.5cm;
    }

    .title {
        color: #000 !important;
        font-size: 24px;
    }

    .title-container {
        margin-top: 1cm;
        margin-bottom: 0.5cm;
    }

    .signature-table {
        width: 80%;
        margin: 0 auto;
        margin-top: 1cm;
        font-size: 14px;
        border-collapse: collapse;
        page-break-inside: avoid;
    }

    .signature-table td {
        border: 1px solid #000;
        padding: 10px;
        text-align: center;
        vertical-align: middle;
    }

    .signature-table p {
        margin: 5px 0;
    }

    .signature-table hr {
        border: none;
        border-top: 1px solid #000;
        width: 220px;
        margin: 5px auto;
    }

    .signature-table .signature-row {
        height: 40px;
    }

    .content-container {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        page-break-inside: avoid;
        min-height: 0;
    }

    .student-page {
        page-break-before: always;
    }

    .student-page:first-child {
        page-break-before: auto;
    }
</style>

<body>
    <header>
        <img src="{{ public_path('img/header.png') }}" style="width: 100%;">
    </header>

    <footer>
        <p>Arjona, calle principal con carrera 2, N° 2-02. municipio Cárdenas estado Táchira</p>
        <p>Teléfonos: 0276-3946955-3940162 / 04147375276</p>
    </footer>

    <main>
        @foreach ($data['students'] as $student)
            <div class="content-container">
                <table class="title-container">
                    <tr>
                        <td colspan="2" style="text-align: center">
                            <b>
                                <label class="title">CERTIFICADO</label>
                                <br>
                                <label class="title">DE EDUCACIÓN PRIMARIA</label>
                            </b>
                        </td>
                    </tr>
                </table>

                <div class="indented-text">
                    <p>
                        Quien suscribe, <strong>Lcda. Luceni Moreno de Arciniegas</strong>, titular de la Cédula de
                        Identidad N° <strong>V-11507972</strong>,
                        Directora de la Institución Educativa: <strong>Unidad Educativa Colegio Virgen del
                            Valle</strong>,
                        ubicada en el
                        Municipio <strong>Cárdenas</strong>, de la parroquia <strong>Táriba</strong>, adscrita al Centro
                        de
                        Desarrollo de la Calidad Educativa
                        Estadal Táchira. Por la presente certifica que el niño:
                        <strong>{{ $student['full_name'] }}</strong>, portador de la Cédula Escolar N°
                        <strong>{{ $student['identity_document'] }}</strong>, nacido en el
                        <strong>{{ $student['birth_place'] ?? 'NO SALE NADA' }}</strong>,
                        en fecha
                        <strong>{{ $student['birthday'] }}</strong>,
                        <strong>cursó el {{ $student['currentGrade'] }} de Educación Primaria</strong> durante el
                        <strong>{{ strtolower($data['term']['name']) }}</strong>,
                        y continuará estudios en el <strong>{{ $student['nextGrade'] }}</strong>,
                        previo cumplimiento de los requisitos establecidos en la normativa legal vigente.
                    </p>
                    <p>
                        Certificado que se expide en <strong>Arjona</strong>, a <strong>{{ $data['date'] }}</strong>.
                    </p>
                </div>

                <table class="signature-table">
                    <tr>
                        <td>
                            <p>INSTITUCIÓN EDUCATIVA (PARA VALIDEZ NACIONAL)</p>
                        </td>
                        <td>
                            <p>CENTRO DE DESARROLLO DE LA CALIDAD EDUCATIVA ESTADAL (PARA VALIDEZ INTERNACIONAL)</p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p>DIRECTOR(A)</p>
                        </td>
                        <td>
                            <p>DIRECTOR(A)</p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p>Nombre y Apellido: Luceni Moreno de A.</p>
                        </td>
                        <td>
                            <p>Nombre y Apellido:</p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p>Número de C.I.: V-11507972</p>
                        </td>
                        <td>
                            <p>Número de C.I.:</p>
                        </td>
                    </tr>
                    <tr>
                        <td class="signature-row">
                            <hr>
                            <p>Firma y Sello:</p>
                        </td>
                        <td class="signature-row">
                            <hr>
                            <p>Firma y Sello:</p>
                        </td>
                    </tr>
                </table>
            </div>
        @endforeach
    </main>
</body>

</html>
