<div>
    <table>
        <thead>
            <tr>
                <th style="border: 1px solid black">NRO</th>
                @if ($type_education_id)
                    <th style="border: 1px solid black">PDF</th>
                    <th style="border: 1px solid black">SOLVENTE</th>
                @endif
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
                $nro = 1;
                // Calculamos colspan dinámico
                $fixedColumnsCount = $type_education_id ? 7 : 5;
                
                // Inicializamos los contadores usando array_fill_keys (Más rápido y sin errores de redelcaring)
                $approvedCount = array_fill_keys($headers, 0);
                $reprobatedCount = array_fill_keys($headers, 0);
                $totalStudentsInSection = 0;
            @endphp

            @foreach ($data as $index => $row)
                {{-- DETECTAR CAMBIO DE SECCIÓN --}}
                @if ($row['section'] !== $previousSection)
                    
                    {{-- IMPRIMIR ESTADÍSTICAS DE LA SECCIÓN ANTERIOR (Si existe) --}}
                    @if ($previousSection !== null)
                        <tr>
                            <td colspan="{{ $fixedColumnsCount }}" style="text-align: right; font-weight: bold; background-color: #b4c6e7; border: 1px solid black;">TOTAL DE ESTUDIANTES APROBADOS</td>
                            @foreach ($headers as $header)
                                <td style="background-color: #b4c6e7; border: 1px solid black;">{{ $approvedCount[$header] }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td colspan="{{ $fixedColumnsCount }}" style="text-align: right; font-weight: bold; background-color: #b4c6e7; border: 1px solid black;">PORCENTAJE DE APROBADOS</td>
                            @foreach ($headers as $header)
                                @php
                                    $percentage = $totalStudentsInSection > 0 ? round(($approvedCount[$header] / $totalStudentsInSection) * 100) : 0;
                                @endphp
                                <td style="background-color: #b4c6e7; border: 1px solid black;">{{ $percentage }}%</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td colspan="{{ $fixedColumnsCount }}" style="text-align: right; font-weight: bold; background-color: #b4c6e7; border: 1px solid black;">TOTAL DE ESTUDIANTES REPROBADOS</td>
                            @foreach ($headers as $header)
                                <td style="background-color: #b4c6e7; border: 1px solid black;">{{ $reprobatedCount[$header] }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td colspan="{{ $fixedColumnsCount }}" style="text-align: right; font-weight: bold; background-color: #b4c6e7; border: 1px solid black;">PORCENTAJE DE REPROBADOS</td>
                            @foreach ($headers as $header)
                                @php
                                    $percentage = $totalStudentsInSection > 0 ? round(($reprobatedCount[$header] / $totalStudentsInSection) * 100) : 0;
                                @endphp
                                <td style="background-color: #b4c6e7; border: 1px solid black;">{{ $percentage }}%</td>
                            @endforeach
                        </tr>

                        {{-- Separador Visual --}}
                        <tr>
                            <td colspan="{{ count($headers) + $fixedColumnsCount }}" style="background-color: #203864; border: 1px solid black; height: 10px;"></td>
                        </tr>
                    @endif

                    {{-- REINICIAR VARIABLES PARA LA NUEVA SECCIÓN --}}
                    @php
                        $previousSection = $row['section'];
                        $nro = 1;
                        $totalStudentsInSection = 0;
                        // Reiniciamos arrays a 0 limpiamente
                        $approvedCount = array_fill_keys($headers, 0);
                        $reprobatedCount = array_fill_keys($headers, 0);
                    @endphp
                @endif

                {{-- PROCESAR CONTADORES DE LA FILA ACTUAL --}}
                @php
                    $totalStudentsInSection++;
                    foreach ($headers as $header) {
                        // Convertir a numero, asumiendo que vacio es null
                        $val = $row[$header] ?? null;
                        
                        if ($val !== null && is_numeric($val)) {
                            $note = floatval($val);
                            // AJUSTE: Criterio de aprobación (>= 10). Cámbialo si es necesario.
                            if ($note >= 10) {
                                $approvedCount[$header]++;
                            } else {
                                $reprobatedCount[$header]++;
                            }
                        }
                    }
                @endphp

                {{-- IMPRIMIR FILA DEL ESTUDIANTE --}}
                <tr>
                    <td style="border: 1px solid black">{{ $nro++ }}</td>

                    @if ($type_education_id)
                        <td style="border: 1px solid black">{{ $row['pdf'] }}</td>
                        <td style="border: 1px solid black">{{ $row['solvencyCertificate'] }}</td>
                    @endif

                    <td style="border: 1px solid black">{{ $row['grade'] }}</td>
                    <td style="border: 1px solid black">{{ $row['section'] }}</td>
                    <td style="border: 1px solid black">{{ $row['identity_document'] }}</td>
                    <td style="border: 1px solid black">{{ $row['full_name'] }}</td>

                    @foreach ($headers as $header)
                        <td style="border: 1px solid black">{{ $row[$header] ?? null }}</td>
                    @endforeach
                </tr>

                {{-- BLOQUE FINAL: Imprimir estadísticas de la ÚLTIMA sección --}}
                @if ($loop->last)
                    <tr>
                        <td colspan="{{ $fixedColumnsCount }}" style="text-align: right; font-weight: bold; background-color: #b4c6e7; border: 1px solid black;">TOTAL DE ESTUDIANTES APROBADOS</td>
                        @foreach ($headers as $header)
                            <td style="background-color: #b4c6e7; border: 1px solid black;">{{ $approvedCount[$header] }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <td colspan="{{ $fixedColumnsCount }}" style="text-align: right; font-weight: bold; background-color: #b4c6e7; border: 1px solid black;">PORCENTAJE DE APROBADOS</td>
                        @foreach ($headers as $header)
                            @php
                                $percentage = $totalStudentsInSection > 0 ? round(($approvedCount[$header] / $totalStudentsInSection) * 100) : 0;
                            @endphp
                            <td style="background-color: #b4c6e7; border: 1px solid black;">{{ $percentage }}%</td>
                        @endforeach
                    </tr>
                    <tr>
                        <td colspan="{{ $fixedColumnsCount }}" style="text-align: right; font-weight: bold; background-color: #b4c6e7; border: 1px solid black;">TOTAL DE ESTUDIANTES REPROBADOS</td>
                        @foreach ($headers as $header)
                            <td style="background-color: #b4c6e7; border: 1px solid black;">{{ $reprobatedCount[$header] }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <td colspan="{{ $fixedColumnsCount }}" style="text-align: right; font-weight: bold; background-color: #b4c6e7; border: 1px solid black;">PORCENTAJE DE REPROBADOS</td>
                        @foreach ($headers as $header)
                            @php
                                $percentage = $totalStudentsInSection > 0 ? round(($reprobatedCount[$header] / $totalStudentsInSection) * 100) : 0;
                            @endphp
                            <td style="background-color: #b4c6e7; border: 1px solid black;">{{ $percentage }}%</td>
                        @endforeach
                    </tr>
                @endif

            @endforeach
        </tbody>
    </table>
</div>