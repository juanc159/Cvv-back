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
        /* Espacio para el header */
        margin-left: 0cm;
        margin-right: 0cm;
        margin-bottom: 3cm;
        /* Aumentamos el margen inferior para el footer */
        min-height: calc(100% - 5.5cm);
        /* Ajustamos para incluir el espacio del footer */
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        /* Distribuye el contenido para centrarlo verticalmente */
        background-image: url({{ public_path('img/background.jpg') }});
        /* Fondo en el body */
        background-repeat: no-repeat;
        background-position: center center;
        /* Centramos la imagen */
        background-size: 70% auto;
        /* Ajustamos el tamaño para que sea visible */
        background-attachment: fixed;
        /* Fondo cubre toda la página */
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
        height: 3cm;
        /* Aumentamos la altura para el contenido del footer */
        text-align: center;
        /* Centramos el texto del footer */
        font-size: 12px;
        /* Tamaño de fuente más pequeño para el footer */
        line-height: 1.2;
        /* Espaciado entre líneas del footer */
        color: #333;
        /* Color del texto */
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
        /* Sangría para el texto principal */
        text-align: justify;
        width: 80%;
        margin-left: auto;
        margin-right: auto;
        font-size: 16px;
        /* Aumentamos el tamaño de la fuente */
        line-height: 1.5;
        /* Interlineado de 1.5 */
    }

    .title {
        color: #000 !important;
        font-size: 24px;
        /* Aumentamos el tamaño del título */
    }

    .title-container {
        margin-top: 2cm;
        /* Más espacio para que el título no esté tan pegado al header */
        margin-bottom: 1cm;
        /* Espacio debajo del título */
    }

    .signature-table {
        width: 500px;
        margin-left: auto;
        margin-right: auto;
        margin-top: 2cm;
        /* Espacio antes de la firma */
        margin-bottom: 3.5cm;
        /* Aumentamos para dejar espacio para el footer */
    }

    .content-container {
        flex: 1;
        /* Permite que el contenido principal ocupe el espacio disponible */
        display: flex;
        flex-direction: column;
        justify-content: center;
        /* Centra el contenido verticalmente */
    }
</style>

<body>
    <!-- IMAGEN DE ENCABEZADO -->
    <header>
        <img src="{{ public_path('img/header.png') }}" style="width: 100%;">
    </header>

    <footer>
        <p>Arjona, calle principal con carrera 2, N° 2-02. Municipio Cárdenas</p>
        <p>Teléfonos: 0276-3946955-3940162 / 04147375276</p>
    </footer>

    <main>
        <div class="content-container">
            <table class="title-container">
                <tr>
                    <td colspan="2" style="text-align: center">
                        <b>
                            <label class="title">CONSTANCIA DE ESTUDIOS</label>
                        </b>
                    </td>
                </tr>
            </table>

            <div class="indented-text">
                <p>
                    Quien suscribe, LCDA. LUCENI MORENO DE ARCINIEGAS, cédula de Identidad N.- 11.507.972, Directora de
                    la {{ $data['student']['company']['name'] }}, de la localidad Arjona, por medio de la presente HACE
                    CONSTAR que el Alumno(a): <strong>{{ $data['student']['full_name'] }}</strong>, portador(a) de la
                    cédula escolar N°
                    <strong>{{ $data['student']['country_id'] == $data['student']['company']['country_id'] ? 'V' : 'E' }}
                        {{ $data['student']['identity_document'] }}</strong>, cursa estudios en esta Institución en el
                    {{ strtoupper($data['student']['grade']['name']) }} DE
                    {{ strtoupper($data['student']['type_education']['name']) }}, para este
                    {{ strtolower($data['term']['name']) }}.
                </p>
                <p>
                    Se expide la presente Constancia a petición de parte interesada para los fines legales que estime
                    conveniente realizar.
                </p>
                <p>
                    En Arjona a {{ $data['date'] }}.
                </p>
            </div>
        </div>

        <!-- Firma al final del documento -->
        <table class="signature-table" align="center">
            <tr>
                <td align="center">
                    <img src="{{ public_path('img/firma.png') }}" style="max-width: 150px !important;">
                </td>
            </tr>
            <tr>
                <td align="center">
                    <span style="font-size: 12px;">Firma Autorizada</span>
                </td>
            </tr>
        </table>
    </main>
</body>

</html>
