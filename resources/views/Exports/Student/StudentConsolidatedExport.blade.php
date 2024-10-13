<div>
    <table>
        <thead>
            <tr>
                <th>NRO</th>
                <th>AÑO</th>
                <th>SECCIÓN</th>
                <th>CÉDULA</th>
                <th>NOMBRES Y APELLIDOS ESTUDIANTE</th>

                @foreach ($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>

        </thead>
        <tbody>
            @php
                $previousSection = null;
                $nro = 1; // Inicializa el contador
            @endphp

            @foreach ($data as $row)
                @if ($row['section'] !== $previousSection)
                    @if ($previousSection !== null)
                        <tr>
                            <td colspan="7" style="background-color: #203864;"></td>
                        </tr>
                    @endif
                    @php
                        $previousSection = $row['section'];
                        $nro = 1; // Reinicia el contador cuando cambia la sección
                    @endphp
                @endif

                <tr>
                    <td>{{ $nro++ }}</td> <!-- Incrementa el contador en cada fila -->
                    <td>{{ $row['grade'] }}</td>
                    <td>{{ $row['section'] }}</td>
                    <td>{{ $row['identity_document'] }}</td>
                    <td>{{ $row['full_name'] }}</td>

                    @foreach ($headers as $header)
                        <!-- Aquí asumes que el valor está en $row y está mapeado a cada header -->
                        <td>{{ $row[$header] ?? null }}</td> <!-- Usa null como valor predeterminado si no existe -->
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
