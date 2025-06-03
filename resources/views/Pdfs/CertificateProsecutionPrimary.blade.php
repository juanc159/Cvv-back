<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<style>
    @page {
        margin: 0cm 0cm;
    }

    * {
        font-family: 'Roboto', sans-serif !important;
    }

    body {
        margin-top: 4cm;
        /* Increased to accommodate taller header */
        margin-left: 0cm;
        margin-right: 0cm;
        margin-bottom: 3cm;
        min-height: calc(100% - 7cm);
        /* Adjusted for new body margin */
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    header {
        position: fixed;
        top: 0cm;
        left: 0cm;
        right: 0cm;
        height: 4cm;
        /* Increased to fit both images */
        display: flex;
        flex-direction: column;
        align-items: center;
        /* Center content horizontally */
    }

    header .main-logo {
        margin-top: 2cm;
        margin-bottom: 2.5cm;
        width: 40%;
        /* Match the width of the indented-text for alignment */
        max-width: 600px;
        /* Prevent oversized images */
        display: block;
        margin-left: 2cm;
        /* Retain original alignment */
        margin-right: auto;
    }

    header .escudo-logo {
        /* Space between images */
        width: 10%;
        /* Smaller size for the escudo */
        max-width: 100px;
        /* Smaller max-width */
        display: block;
        margin-left: -1.3cm;
        margin-right: 0px;
        /* Center horizontally */
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

    .title-container {
        margin-top: 0.5cm;
        margin-bottom: 0.3cm;
        width: 100%;
    }

    .title {
        color: #000 !important;
        font-size: 18px;
        font-weight: bold;
        text-align: center;
        line-height: 1.2;
    }

    .title-container hr {
        border: none;
        border-top: 1px solid #000;
        width: 50%;
        margin: 0 auto 0.2cm auto;
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
        text-align: left;
        vertical-align: top;
    }

    .signature-table p {
        margin: 0;
        text-align: left;
    }

    .signature-table .signature-row {
        height: 150px;
    }

    .content-container {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        page-break-inside: avoid;
        min-height: 0;
    }

    /* Salto de página para cada estudiante, asegurando que cada uno sea independiente */
    .student-page {
        page-break-before: always;
    }

    .student-page:first-child {
        page-break-before: auto;
    }
</style>

<body>
    <!-- IMAGEN DE ENCABEZADO -->
    <header>
        <img src="{{ public_path('img/Header-Mpppd.png') }}" class="main-logo">
        <img src="{{ public_path('img/escudo_de_venezuela.png') }}" class="escudo-logo">
    </header>

    <footer>
    </footer>

    <main>
        @foreach ($data['students'] as $student)
            <div class="content-container">
                <table class="title-container">
                    <tr>
                        <td colspan="2" style="text-align: center">
                            <b>
                                <label class="title">{{ $data['titlePdf'] }}</label>
                                <br>
                                <label class="title">{{ $data['subTitlePdf'] }}</label>
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
                        <strong>cursó el {{ $student['currentGrade'] }} de Educación
                            Primaria</strong>, correspondiéndole el literal “{{ $student['literal'] }}” durante el
                        <strong>{{ strtolower($data['term']['name']) }}</strong>,
                        y continuará estudios en el <strong>{{ $student['nextGrade'] }}</strong>,
                        previo cumplimiento de los requisitos establecidos en la normativa legal vigente.
                    </p>
                    <p>
                        Certificado que se expide en <strong>Arjona</strong>, a {!! $data['date'] !!}.
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
                            <p>Firma y Sello:</p>
                        </td>
                        <td class="signature-row">
                            <p>Firma y Sello:</p>
                        </td>
                    </tr>
                </table>
            </div>
        @endforeach
    </main>
</body>

</html>
