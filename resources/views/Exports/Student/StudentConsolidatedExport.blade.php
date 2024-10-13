<div>
    <table>
        <thead>
            <tr>
                <th style="border: 1px solid black">NRO</th>
                <th style="border: 1px solid black">AÑO</th>
                <th style="border: 1px solid black">SECCIÓN</th>
                <th style="border: 1px solid black">CÉDULA</th>
                <th style="border: 1px solid black">NOMBRES Y APELLIDOS ESTUDIANTE</th>

                @foreach ($headers as $header)
                    <th style="border: 1px solid black">{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php
                $previousSection = null;
                $nro = 1; // Inicializa el contador
                $colspanValue = count($headers) + 5; // Calcula el colspan
            @endphp

            @foreach ($data as $row)
                @if ($row['section'] !== $previousSection)
                    @if ($previousSection !== null)
                        <tr>
                            <td colspan="{{ $colspanValue }}" style="background-color: #203864; border: 1px solid black"></td>
                        </tr>
                    @endif
                    @php
                        $previousSection = $row['section'];
                        $nro = 1; // Reinicia el contador cuando cambia la sección
                    @endphp
                @endif

                <tr>
                    <td style="border: 1px solid black">{{ $nro++ }}</td>
                    <td style="border: 1px solid black">{{ $row['grade'] }}</td>
                    <td style="border: 1px solid black">{{ $row['section'] }}</td>
                    <td style="border: 1px solid black">{{ $row['identity_document'] }}</td>
                    <td style="border: 1px solid black">{{ $row['full_name'] }}</td>

                    @foreach ($headers as $header)
                        <td style="border: 1px solid black">{{ $row[$header] ?? null }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
