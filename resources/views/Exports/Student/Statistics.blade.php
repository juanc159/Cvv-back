<div class="container" style="margin-top: 20px;">
    <h2 style=" color: #333; font-family: Arial, sans-serif;">Estadísticas de Estudiantes</h2>
    <table class="table table-bordered" style="border: 2px solid #ccc; border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; font-size: 14px;">
        <thead>
            <tr style="background-color: #f8f9fa; color: #333; font-weight: bold; ">
                <th style="border-right: 2px solid #666; padding: 10px; background-color: #e9ecef;">Tipo de Educación</th>
                <th style="border-right: 2px solid #666; padding: 10px; background-color: #e9ecef;">Grado y Sección</th>
                <th colspan="3" style="background-color: #4199cc; color: white; padding: 10px;">Matrícula Inicial</th>
                <th colspan="3" style="border-right: 2px solid #666; background-color: #007bff; color: white; padding: 10px;">Ingresos</th>
                <th colspan="3" style="border-right: 2px solid #666; background-color: #dc3545; color: white; padding: 10px;">Egresos</th>
                <th colspan="3" style="border-right: 2px solid #666; background-color: #28a745; color: white; padding: 10px;">Nacionales</th>
                <th colspan="3" style="background-color: #ffc107; color: white; padding: 10px;">Extranjeros</th>
                
            </tr>
            <tr style="background-color: #f8f9fa; color: #333; font-weight: bold; ">
                <th style="border-right: 2px solid #666;"></th>
                <th style="border-right: 2px solid #666;"></th>
                <th style="background-color: #4199cc; color: white; padding: 8px;">M</th>
                <th style="background-color: #4199cc; color: white; padding: 8px;">F</th>
                <th style="background-color: #4199cc; color: white; padding: 8px;">T</th>
                <th style="background-color: #007bff; color: white; padding: 8px;">M</th>
                <th style="background-color: #007bff; color: white; padding: 8px;">F</th>
                <th style="border-right: 2px solid #666; background-color: #007bff; color: white; padding: 8px;">T</th>
                <th style="background-color: #dc3545; color: white; padding: 8px;">M</th>
                <th style="background-color: #dc3545; color: white; padding: 8px;">F</th>
                <th style="border-right: 2px solid #666; background-color: #dc3545; color: white; padding: 8px;">T</th>
                <th style="background-color: #28a745; color: white; padding: 8px;">M</th>
                <th style="background-color: #28a745; color: white; padding: 8px;">F</th>
                <th style="border-right: 2px solid #666; background-color: #28a745; color: white; padding: 8px;">T</th>
                <th style="background-color: #ffc107; color: white; padding: 8px;">M</th>
                <th style="background-color: #ffc107; color: white; padding: 8px;">F</th>
                <th style="background-color: #ffc107; color: white; padding: 8px;">T</th>
            </tr>
        </thead>
        <tbody>
            @php
                $currentType = null;
                $subtotales = [];
                $typeRowCount = 0;
                $rowColors = ['#ffffff', '#f0f8ff']; // Colores alternativos para filas
                $rowIndex = 0;
            @endphp
            
            @foreach($statistics as $index => $stat)
                @if($stat['type_education_name'] != $currentType)
                    @if($currentType !== null)
                        {{-- Mostrar subtotal del tipo anterior --}}
                        <tr style="background-color: #cce5ff; font-weight: bold; "> 
                            <td style="font-weight: bold; border-right: 2px solid #666; background-color: #d1e7dd; padding: 8px;">
                                Subtotal {{ $currentType }}
                            </td>
                            <td>{{ $subtotales['ingresos_male'] }}</td>
                            <td>{{ $subtotales['ingresos_female'] }}</td>
                            <td style="border-right: 2px solid #666;">{{ $subtotales['ingresos_total'] }}</td>
                            <td>{{ $subtotales['egresos_male'] }}</td>
                            <td>{{ $subtotales['egresos_female'] }}</td>
                            <td style="border-right: 2px solid #666;">{{ $subtotales['egresos_total'] }}</td>
                            <td>{{ $subtotales['nacionales_male'] }}</td>
                            <td>{{ $subtotales['nacionales_female'] }}</td>
                            <td style="border-right: 2px solid #666;">{{ $subtotales['nacionales_total'] }}</td>
                            <td>{{ $subtotales['extranjeros_male'] }}</td>
                            <td>{{ $subtotales['extranjeros_female'] }}</td>
                            <td>{{ $subtotales['extranjeros_total'] }}</td>
                        </tr>
                    @endif
                    
                    @php
                        $currentType = $stat['type_education_name'];
                        $subtotales = array_fill_keys([
                            'ingresos_male', 'ingresos_female', 'ingresos_total',
                            'egresos_male', 'egresos_female', 'egresos_total',
                            'nacionales_male', 'nacionales_female', 'nacionales_total',
                            'extranjeros_male', 'extranjeros_female', 'extranjeros_total', 
                            'previos_male', 'previos_female', 'previos_total',  
                        ], 0);
                        
                        $typeRows = $statistics->where('type_education_name', $currentType);
                        $typeRowCount = $typeRows->count();
                        $rowIndex = 0; // Reiniciar el índice de filas
                    @endphp
                    
                    <tr style="background-color: {{ $rowColors[$rowIndex % 2] }};">
                        <td rowspan="{{ $typeRowCount + 1 }}" style="vertical-align: middle; border-right: 2px solid #666; background-color: #e9ecef; padding: 10px;">
                            {{ $currentType }}
                        </td> 
                        <td style="border-right: 2px solid #666; padding: 8px;">{{ $stat['grade_name'] }} - {{ $stat['section_name'] }}</td>
                        <td>{{ $stat['previos_male'] }}</td>
                        <td>{{ $stat['previos_female'] }}</td>
                        <td style="border-right: 2px solid #666;">{{ $stat['previos_total'] }}</td>
                        <td>{{ $stat['ingresos_male'] }}</td>
                        <td>{{ $stat['ingresos_female'] }}</td>
                        <td style="border-right: 2px solid #666;">{{ $stat['ingresos_total'] }}</td>
                        <td>{{ $stat['egresos_male'] }}</td>
                        <td>{{ $stat['egresos_female'] }}</td>
                        <td style="border-right: 2px solid #666;">{{ $stat['egresos_total'] }}</td>
                        <td>{{ $stat['nacionales_male'] }}</td>
                        <td>{{ $stat['nacionales_female'] }}</td>
                        <td style="border-right: 2px solid #666;">{{ $stat['nacionales_total'] }}</td>
                        <td>{{ $stat['extranjeros_male'] }}</td>
                        <td>{{ $stat['extranjeros_female'] }}</td>
                        <td>{{ $stat['extranjeros_total'] }}</td>
                    </tr>
                @else
                    <tr style="background-color: {{ $rowColors[++$rowIndex % 2] }};">
                        <td style="border-right: 2px solid #666; padding: 8px;">{{ $stat['grade_name'] }} - {{ $stat['section_name'] }}</td>
                        <td>{{ $stat['previos_male'] }}</td>
                        <td>{{ $stat['previos_female'] }}</td>
                        <td style="border-right: 2px solid #666;">{{ $stat['previos_total'] }}</td>
                        <td>{{ $stat['ingresos_male'] }}</td>
                        <td>{{ $stat['ingresos_female'] }}</td>
                        <td style="border-right: 2px solid #666;">{{ $stat['ingresos_total'] }}</td>
                        <td>{{ $stat['egresos_male'] }}</td>
                        <td>{{ $stat['egresos_female'] }}</td>
                        <td style="border-right: 2px solid #666;">{{ $stat['egresos_total'] }}</td>
                        <td>{{ $stat['nacionales_male'] }}</td>
                        <td>{{ $stat['nacionales_female'] }}</td>
                        <td style="border-right: 2px solid #666;">{{ $stat['nacionales_total'] }}</td>
                        <td>{{ $stat['extranjeros_male'] }}</td>
                        <td>{{ $stat['extranjeros_female'] }}</td>
                        <td>{{ $stat['extranjeros_total'] }}</td>
                    </tr>
                @endif
                
                @php
                    foreach ($subtotales as $key => $value) {
                        $subtotales[$key] += $stat[$key];
                    }
                @endphp
            @endforeach
            
            {{-- Último subtotal --}}
            @if($currentType !== null)
                <tr style="background-color: #cce5ff; font-weight: bold; "> 
                    <td style="font-weight: bold; border-right: 2px solid #666; background-color: #d1e7dd; padding: 8px;">
                        Subtotal {{ $currentType }}
                    </td>
                    <td>{{ $subtotales['previos_male'] }}</td>
                    <td>{{ $subtotales['previos_female'] }}</td>
                    <td style="border-right: 2px solid #666;">{{ $subtotales['previos_total'] }}</td>
                    <td>{{ $subtotales['ingresos_male'] }}</td>
                    <td>{{ $subtotales['ingresos_female'] }}</td>
                    <td style="border-right: 2px solid #666;">{{ $subtotales['ingresos_total'] }}</td>
                    <td>{{ $subtotales['egresos_male'] }}</td>
                    <td>{{ $subtotales['egresos_female'] }}</td>
                    <td style="border-right: 2px solid #666;">{{ $subtotales['egresos_total'] }}</td>
                    <td>{{ $subtotales['nacionales_male'] }}</td>
                    <td>{{ $subtotales['nacionales_female'] }}</td>
                    <td style="border-right: 2px solid #666;">{{ $subtotales['nacionales_total'] }}</td>
                    <td>{{ $subtotales['extranjeros_male'] }}</td>
                    <td>{{ $subtotales['extranjeros_female'] }}</td>
                    <td>{{ $subtotales['extranjeros_total'] }}</td>
                </tr>
            @endif
        </tbody>
        <tfoot>
            <tr style="background-color: #e9ecef; font-weight: bold; ">
                <td colspan="2" style="font-weight: bold; border-right: 2px solid #666; padding: 10px; text-align: right;">
                    Total General
                </td>
                <td>{{ $statistics->sum('previos_male') }}</td>
                <td>{{ $statistics->sum('previos_female') }}</td>
                <td style="border-right: 2px solid #666;">{{ $statistics->sum('previos_total') }}</td>
                <td>{{ $statistics->sum('ingresos_male') }}</td>
                <td>{{ $statistics->sum('ingresos_female') }}</td>
                <td style="border-right: 2px solid #666;">{{ $statistics->sum('ingresos_total') }}</td>
                <td>{{ $statistics->sum('egresos_male') }}</td>
                <td>{{ $statistics->sum('egresos_female') }}</td>
                <td style="border-right: 2px solid #666;">{{ $statistics->sum('egresos_total') }}</td>
                <td>{{ $statistics->sum('nacionales_male') }}</td>
                <td>{{ $statistics->sum('nacionales_female') }}</td>
                <td style="border-right: 2px solid #666;">{{ $statistics->sum('nacionales_total') }}</td>
                <td>{{ $statistics->sum('extranjeros_male') }}</td>
                <td>{{ $statistics->sum('extranjeros_female') }}</td>
                <td>{{ $statistics->sum('extranjeros_total') }}</td>
            </tr>
        </tfoot>
    </table>
</div>

{{-- En tu blade, después del cierre del primer container --}}
<div class="container" style="margin-top: 40px;">
    <h2 style="color: #333; font-family: Arial, sans-serif;">Estudiantes Retirados</h2>
    <table class="table table-bordered" style="border: 2px solid #ccc; border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; font-size: 14px;">
        <thead>
            <tr style="background-color: #f8f9fa; color: #333; font-weight: bold;">
                <th style="padding: 10px; border-right: 2px solid #666;">Documento</th>
                <th style="padding: 10px; border-right: 2px solid #666;">Apellidos y Nombres</th>
                <th style="padding: 10px; border-right: 2px solid #666;">Fecha Nacimiento</th>
                <th style="padding: 10px; border-right: 2px solid #666;">Grado</th>
                <th style="padding: 10px; border-right: 2px solid #666;">Sección</th>
                <th style="padding: 10px; border-right: 2px solid #666;">Sexo</th>
                <th style="padding: 10px; border-right: 2px solid #666;">Fecha Retiro</th>
                <th style="padding: 10px;">Motivo</th>
            </tr>
        </thead>
        <tbody>
            @forelse($withdrawnStudents as $student)
                <tr style="background-color: {{ $loop->even ? '#f8f9fa' : '#ffffff' }};">
                    <td style="padding: 8px; border-right: 2px solid #666;">{{ $student->identity_document }}</td>
                    <td style="padding: 8px; border-right: 2px solid #666;">{{ $student->full_name }}</td>
                    <td style="padding: 8px; border-right: 2px solid #666;">
                        @if($student->birthday)
                            {{ Carbon\Carbon::parse($student->birthday)->format('d/m/Y') }}
                        @else
                            -
                        @endif
                    </td>
                    <td style="padding: 8px; border-right: 2px solid #666;">{{ $student->grade_name }}</td>
                    <td style="padding: 8px; border-right: 2px solid #666;">{{ $student->section_name }}</td>
                    <td style="padding: 8px; border-right: 2px solid #666;">{{ $student->gender == 'M' ? 'Masculino' : 'Femenino' }}</td>
                    <td style="padding: 8px; border-right: 2px solid #666;">
                        {{ Carbon\Carbon::parse($student->withdrawal_date)->format('d/m/Y') }}
                    </td>
                    <td style="padding: 8px;">{{ $student->reason }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 15px;">No hay estudiantes retirados registrados</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>