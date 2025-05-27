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
        text-align: right;
        font-size: 14px;
        /* Aumentado de 12px a 14px para que las letras sean más grandes */
        line-height: 1.5;
        width: 40%;
    }

    .signature-section p {
        margin: 0;
        padding: 0;
    }

    .signature-section hr {
        border: none;
        border-top: 1px solid #000;
        width: 220px;
        /* Aumentado de 100px a 120px para que la línea sea más visible */
        margin: 5px 0;
        margin-left: auto;
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
                            <label class="title">CONSTANCIA DE RETIRO</label>
                        </b>
                    </td>
                </tr>
            </table>

            <div class="indented-text">
                <p>
                    Quien suscribe, LCDA. LUCENI MORENO DE ARCINIEGAS, cédula de Identidad N° V-11507972, directora
                    de
                    la UNIDAD EDUCATIVA COLEGIO VIRGEN DEL VALLE, ubicada en la localidad Arjona municipio Cárdenas
                    estado Táchira, por
                    medio de la presente hace
                    constar que el Alumno(a): <strong>{{ $data['student']['full_name'] }}</strong>, portador(a)
                    {{ $data['student']['type_document_name'] }} N°
                    <strong>{{ $data['student']['country_id'] == $data['student']['company']['country_id'] ? 'V-' : 'E-' }}{{ $data['student']['identity_document'] }}</strong>,



                    fue retirado(a) de esta institución por solicitud de su representante, el (la) ciudadano(a):
                    {{ $data['additionalInfo']['name'] }}, cédula de identidad Nº
                    {{ $data['additionalInfo']['documentType'] }}-{{ $data['additionalInfo']['documentNumber'] }},
                    representante legal del estudiante.
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
