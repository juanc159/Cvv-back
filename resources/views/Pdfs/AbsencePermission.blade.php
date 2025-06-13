<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Constancia de Estudios</title>
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
    }

    .title {
        color: #000 !important;
        font-size: 24px;
    }

    .title-container {
        margin-top: 3cm;
        margin-bottom: 1cm;
    }

    .signature-section {
        position: absolute;
        bottom: 5cm;
        right: 1cm;
        text-align: center;
        /* Changed to center to align text and hr within the div */
        font-size: 14px;
        line-height: 1.5;
        width: 40%;
        /* Kept original width */
    }

    .signature-section p {
        margin: 0;
        padding: 0;
    }

    .signature-section hr {
        border: none;
        border-top: 1px solid #000;
        width: 220px;
        margin: 5px auto;
        /* Changed to auto to center the hr within the div */
    }

    .content-container {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
</style>

<body>
    <!-- IMAGEN DE ENCABEZADO -->
    <header>
        <img src="{{ public_path('img/header.png') }}" style="width: 100%;">
    </header>

    <footer>
        <p>Arjona, calle principal con carrera 2, N° 2-02. municipio Cárdenas estado Táchira</p>
        <p>Teléfonos: 0276-3946955-3940162 / 04147375276</p>
    </footer>

    <main>
        <div class="content-container">
            <table class="title-container">
                <tr>
                    <td colspan="2" style="text-align: center">
                        <b>
                            <label class="title">PERMISO DE AUSENCIA ESCOLAR</label>
                        </b>
                    </td>
                </tr>
            </table>

            <div class="indented-text">
                <p>
                    Quien suscribe, LCDA. LUCENI MORENO DE ARCINIEGAS, cédula de Identidad N° V-11507972, directora
                    de
                    la UNIDAD EDUCATIVA COLEGIO VIRGEN DEL VALLE, ubicada en la localidad de Arjona municipio Cárdenas
                    estado Táchira, por
                    medio de la presente hace
                    constar que el alumno(a): <strong>{{ $data['student']['full_name'] }}</strong>, portador(a)
                    {{ $data['student']['type_document_name'] }} N°
                    <strong>{{ $data['student']['country_id'] == $data['student']['company']['country_id'] ? 'V-' : 'E-' }}{{ $data['student']['identity_document'] }}</strong>,


                    estudiante del {{ mb_strtoupper($data['student']['grade']['name'], 'UTF-8') }} DE
                    {{ mb_strtoupper($data['student']['type_education']['name'], 'UTF-8') }}, en nuestra institución,
                    para ausentarse de sus actividades escolares a partir del día
                    {{ \Carbon\Carbon::parse($data['additionalInfo']['startDate'])->locale('es')->isoFormat('dddd D \d\e MMMM') }},
                    debiendo integrarse a sus actividades escolares regulares a partir del día
                    {{ \Carbon\Carbon::parse($data['additionalInfo']['endDate'])->locale('es')->isoFormat('dddd D \d\e MMMM') }}.


                </p>
                <p>
                    Se expide la presente constancia a petición de parte interesada para los fines legales que estime
                    conveniente realizar.
                </p>
                <p>
                    En Arjona, a {{ $data['date'] }}.
                </p>
            </div>
        </div>

        <div class="signature-section">
            <hr>
            <p>Lcda. Luceni Moreno de Arciniegas</p>
            <p>Directora</p>
        </div>
    </main>
</body>

</html>
