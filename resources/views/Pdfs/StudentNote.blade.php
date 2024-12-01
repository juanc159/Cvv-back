<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ejemplo de Botón Bootstrap</title>
</head>
<style>
    @page {
        margin: 0cm 0cm;
    }

    * {
        font-family: 'Roboto', sans-serif !important;
    }

    /** Defina ahora los márgenes reales de cada página en el PDF **/
    body {
        margin-top: 2.5cm;
        margin-left: 0cm;
        margin-right: 0cm;
        margin-bottom: 2.2cm;
    }

    /** Definir las reglas del encabezado **/
    header {
        position: fixed;
        top: 0cm;
        left: 0cm;
        right: 0cm;
        height: 3cm;
    }

    /** Definir las reglas del pie de página **/
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
        /* Ajusta el valor según la cantidad de espacio que desees */
    }

    td {
        border-radius: 5px;
        padding: 5px;
        /* Agrega un relleno para separar el contenido de los bordes */
    }

    .text-media {
        font-size: 14px;
    }
</style>

<body>



    <!-- IMAGEN DE ENCABEZADO -->
    <header>
        <img src="{{ public_path('img/header.png') }}"
            style="width: 100%;">
    </header>

    <footer>
        {{-- <img src="https://tracegt.housebl.com:7443/images/FOOTER.jpg" style="width: 100%;"> --}}
    </footer>

    <main>

        <div
            style="text-align: center; background-image: url({{ public_path('img/background.jpg') }});  background-repeat: no-repeat ;  background-position: center; background-size: 50% auto;">

            <table>
                <tr>
                    <td colspan="2" style="text-align: center">
                        <b>
                            <label style=" color: #000 !important; font-size: 20px; ">Calificaciones Acumulativas <br>
                                {{ $data['student']['typeEducation']['name'] }}</label></b>
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
            <table style="width: 500px;" align="center">

                <tr>
                    <th>&nbsp;</th>
                    @for ($i = 1; $i <= $data['student']['typeEducation']['cantNotes']; $i++)
                        <th>NOTA {{ $i }}</th>
                    @endfor
                </tr>
                @foreach ($data['student']['notes'] as $key => $nota)
                    <tr>
                        <td style="text-align: center; font-size: 15px;"><span> {{ $nota['subject']['name'] }}</span>
                        </td>

                        @php
                            $valores = json_decode($nota['json'], 1);
                        @endphp
                        @foreach ($valores as $key => $val)
                            <td
                                style="border: 1px solid rgb(119, 119, 119); padding-left: 8px; padding-right: 8px; padding: 4px; text-align: center;">
                                <span>{{ $val }}</span>
                            </td>
                        @endforeach
                    </tr>
                @endforeach


            </table>
            <div style="width: 100%; text-align: center; margin-top: 50px;">
                <span style="font-size: 12px; font-style: italic;">U.E. COLEGIO VIRGEN DEL VALLE ¡DÓNDE LA EDUCACIÓN DEL
                    FUTURO ES HOYs!</span>
            </div>
            <table style="500px">
                <tr>
                    <td align="center">
                        <img src="{{ public_path('img/firma.png') }}" style="max-width: 150px !important;">
                        {{-- <img src="https://quwonh.stripocdn.email/content/guids/CABINET_9f6aac012eefaebdd0a78bd7310efde25b299a63237f8900b2018ccd3e36252a/images/captura_de_pantalla_20240128_225603.png"
                            style="max-width: 150px !important;"> --}}
                    </td>
                </tr>
                <tr>
                    <td align="center">
                        @php

                        @endphp


                        <span>{{ $data['date'] }}</span>
                    </td>
                </tr>
            </table>
        </div>
    </main>

    <!-- Incluye el script de jQuery (requerido por Bootstrap) y Bootstrap desde un CDN -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
