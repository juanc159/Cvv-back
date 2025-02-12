<div class="container" style="margin-top: 20px; font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
    <!-- Encabezado de Estadísticas en una Tabla -->
<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
    <tr>
        <td colspan="14" style="text-align: center; font-size: 24px; font-weight: bold; color: #333; padding: 10px;">
            Estadísticas de Estudiantes
        </td>
    </tr>
    <tr>
        <td colspan="14" style="text-align: center; font-size: 16px; color: #555; padding: 5px;">
            Fecha Inicial: {{ $dateInitial }} - Fecha Final: {{ $dateEnd }}
        </td>
    </tr>
</table>
    <table class="table table-bordered" style="border: 2px solid #ccc; border-collapse: collapse; width: 100%; font-size: 14px; border-radius: 8px; overflow: hidden;">
        <thead>
            <tr style="background-color: #f8f9fa; color: #333; font-weight: bold; text-align: center;">
                <th style=" text-align: center; border-right: 2px solid #666; padding: 12px; background-color: #e9ecef; font-size: 15px;">Tipo de Educación</th>
                <th style=" text-align: center; border-right: 2px solid #666; padding: 12px; background-color: #e9ecef; font-size: 15px;">Grado y Sección</th>
                <th colspan="3" style=" text-align: center; background-color: #4199cc; color: white; padding: 12px; font-size: 15px;">Matrícula Inicial</th>
                <th colspan="3" style=" text-align: center; border-right: 2px solid #666; background-color: #007bff; color: white; padding: 12px; font-size: 15px;">Ingresos</th>
                <th colspan="3" style=" text-align: center; border-right: 2px solid #666; background-color: #dc3545; color: white; padding: 12px; font-size: 15px;">Egresos</th>
                <th colspan="3" style=" text-align: center; background-color: #28a745; color: white; padding: 12px; font-size: 15px;">Matrícula Actual</th>
            </tr>
            <tr style="background-color: #f8f9fa; color: #333; font-weight: bold; text-align: center;">
                <th></th>
                <th></th>
                <th style="text-align: center; padding: 10px; font-size: 14px;">M</th>
                <th style="text-align: center; padding: 10px; font-size: 14px;">F</th>
                <th style="text-align: center; padding: 10px; font-size: 14px;">T</th>
                <th style="text-align: center; padding: 10px; font-size: 14px;">M</th>
                <th style="text-align: center; padding: 10px; font-size: 14px;">F</th>
                <th style="text-align: center; padding: 10px; font-size: 14px;">T</th>
                <th style="text-align: center; padding: 10px; font-size: 14px;">M</th>
                <th style="text-align: center; padding: 10px; font-size: 14px;">F</th>
                <th style="text-align: center; padding: 10px; font-size: 14px;">T</th>
                <th style="text-align: center; padding: 10px; font-size: 14px;">M</th>
                <th style="text-align: center; padding: 10px; font-size: 14px;">F</th>
                <th style="text-align: center; padding: 10px; font-size: 14px;">T</th>
            </tr>
        </thead>
        <tbody>
            @php
                $currentTypeEducation = null;
                $totals = [
                    'initial' => ['male' => 0, 'female' => 0, 'total' => 0],
                    'new_entries' => ['male' => 0, 'female' => 0, 'total' => 0],
                    'withdrawals' => ['male' => 0, 'female' => 0, 'total' => 0],
                    'current' => ['male' => 0, 'female' => 0, 'total' => 0],
                ];
                $subtotals = [
                    'initial' => ['male' => 0, 'female' => 0, 'total' => 0],
                    'new_entries' => ['male' => 0, 'female' => 0, 'total' => 0],
                    'withdrawals' => ['male' => 0, 'female' => 0, 'total' => 0],
                    'current' => ['male' => 0, 'female' => 0, 'total' => 0],
                ];
            @endphp
            @foreach($statistics as $stat)
                @if ($stat['type_education_name'] != $currentTypeEducation && $currentTypeEducation !== null)
                    <tr style="font-weight: bold; background-color: #f2f2f2; text-align: center;">
                        <td colspan="2" style="text-align: center; padding: 12px; font-size: 16px; background-color: #e9ecef;">Subtotal {{ $currentTypeEducation }}</td>
                        <td style="text-align: center; padding: 12px; font-size: 16px; background-color: #e9ecef;">{{ $subtotals['initial']['male'] }}</td>
                        <td style="text-align: center; padding: 12px; font-size: 16px; background-color: #e9ecef;">{{ $subtotals['initial']['female'] }}</td>
                        <td style="text-align: center; padding: 12px; font-size: 16px; background-color: #e9ecef;">{{ $subtotals['initial']['total'] }}</td>
                        <td style="text-align: center; padding: 12px; font-size: 16px; background-color: #e9ecef;">{{ $subtotals['new_entries']['male'] }}</td>
                        <td style="text-align: center; padding: 12px; font-size: 16px; background-color: #e9ecef;">{{ $subtotals['new_entries']['female'] }}</td>
                        <td style="text-align: center; padding: 12px; font-size: 16px; background-color: #e9ecef;">{{ $subtotals['new_entries']['total'] }}</td>
                        <td style="text-align: center; padding: 12px; font-size: 16px; background-color: #e9ecef;">{{ $subtotals['withdrawals']['male'] }}</td>
                        <td style="text-align: center; padding: 12px; font-size: 16px; background-color: #e9ecef;">{{ $subtotals['withdrawals']['female'] }}</td>
                        <td style="text-align: center; padding: 12px; font-size: 16px; background-color: #e9ecef;">{{ $subtotals['withdrawals']['total'] }}</td>
                        <td style="text-align: center; padding: 12px; font-size: 16px; background-color: #e9ecef;">{{ $subtotals['current']['male'] }}</td>
                        <td style="text-align: center; padding: 12px; font-size: 16px; background-color: #e9ecef;">{{ $subtotals['current']['female'] }}</td>
                        <td style="text-align: center; padding: 12px; font-size: 16px; background-color: #e9ecef;">{{ $subtotals['current']['total'] }}</td>
                    </tr>
                    @php
                        // Reiniciar los subtotales para el nuevo tipo de educación
                        $subtotals = [
                            'initial' => ['male' => 0, 'female' => 0, 'total' => 0],
                            'new_entries' => ['male' => 0, 'female' => 0, 'total' => 0],
                            'withdrawals' => ['male' => 0, 'female' => 0, 'total' => 0],
                            'current' => ['male' => 0, 'female' => 0, 'total' => 0],
                        ];
                    @endphp
                @endif
                <tr style="text-align: center; background-color: #fff; border-bottom: 1px solid #ddd;">
                    @if ($stat['type_education_name'] != $currentTypeEducation)
                        @php
                            $countTypeEducation = collect($statistics)->filter(function($item) use ($stat) {
                                return $item['type_education_name'] === $stat['type_education_name'];
                            })->count();
                            $currentTypeEducation = $stat['type_education_name']; // Actualiza el tipo de educación actual
                        @endphp
                        <td rowspan="{{ $countTypeEducation }}" style="text-align: center; vertical-align: center; line-height: 1; padding: 12px; font-weight: bold; background-color: #f1f1f1; border-right: 1px solid #ddd;">{{ $stat['type_education_name'] }}</td>
                    @endif
                    <td style="text-align: center; padding: 12px; border-right: 1px solid #ddd;">{{ $stat['grade_name'] }} - {{ $stat['section_name'] }}</td>
                    <td style="text-align: center; padding: 12px; border-right: 1px solid #ddd; background-color: #e0f2f7;">{{ $stat['initial']['male'] }}</td>
                    <td style="text-align: center; padding: 12px; border-right: 1px solid #ddd; background-color: #e0f2f7;">{{ $stat['initial']['female'] }}</td>
                    <td style="text-align: center; padding: 12px; border-right: 1px solid #ddd; background-color: #e0f2f7;">{{ $stat['initial']['total'] }}</td>
                    <td style="text-align: center; padding: 12px; border-right: 1px solid #ddd; background-color: #e3f2fd;">{{ $stat['new_entries']['male'] }}</td>
                    <td style="text-align: center; padding: 12px; border-right: 1px solid #ddd; background-color: #e3f2fd;">{{ $stat['new_entries']['female'] }}</td>
                    <td style="text-align: center; padding: 12px; border-right: 1px solid #ddd; background-color: #e3f2fd;">{{ $stat['new_entries']['total'] }}</td>
                    <td style="text-align: center; padding: 12px; border-right: 1px solid #ddd; background-color: #ffebee;">{{ $stat['withdrawals']['male'] }}</td>
                    <td style="text-align: center; padding: 12px; border-right: 1px solid #ddd; background-color: #ffebee;">{{ $stat['withdrawals']['female'] }}</td>
                    <td style="text-align: center; padding: 12px; border-right: 1px solid #ddd; background-color: #ffebee;">{{ $stat['withdrawals']['total'] }}</td>
                    <td style="text-align: center; padding: 12px; border-right: 1px solid #ddd; background-color: #c8e6c9;">{{ $stat['current']['male'] }}</td>
                    <td style="text-align: center; padding: 12px; border-right: 1px solid #ddd; background-color: #c8e6c9;">{{ $stat['current']['female'] }}</td>
                    <td style="text-align: center; padding: 12px; background-color: #c8e6c9;">{{ $stat['current']['total'] }}</td>
                    @php
                        // Acumular los totales y subtotales
                        $totals['initial']['male'] += $stat['initial']['male'];
                        $totals['initial']['female'] += $stat['initial']['female'];
                        $totals['initial']['total'] += $stat['initial']['total'];
                        $totals['new_entries']['male'] += $stat['new_entries']['male'];
                        $totals['new_entries']['female'] += $stat['new_entries']['female'];
                        $totals['new_entries']['total'] += $stat['new_entries']['total'];
                        $totals['withdrawals']['male'] += $stat['withdrawals']['male'];
                        $totals['withdrawals']['female'] += $stat['withdrawals']['female'];
                        $totals['withdrawals']['total'] += $stat['withdrawals']['total'];
                        $totals['current']['male'] += $stat['current']['male'];
                        $totals['current']['female'] += $stat['current']['female'];
                        $totals['current']['total'] += $stat['current']['total'];
        
                        $subtotals['initial']['male'] += $stat['initial']['male'];
                        $subtotals['initial']['female'] += $stat['initial']['female'];
                        $subtotals['initial']['total'] += $stat['initial']['total'];
                        $subtotals['new_entries']['male'] += $stat['new_entries']['male'];
                        $subtotals['new_entries']['female'] += $stat['new_entries']['female'];
                        $subtotals['new_entries']['total'] += $stat['new_entries']['total'];
                        $subtotals['withdrawals']['male'] += $stat['withdrawals']['male'];
                        $subtotals['withdrawals']['female'] += $stat['withdrawals']['female'];
                        $subtotals['withdrawals']['total'] += $stat['withdrawals']['total'];
                        $subtotals['current']['male'] += $stat['current']['male'];
                        $subtotals['current']['female'] += $stat['current']['female'];
                        $subtotals['current']['total'] += $stat['current']['total'];
                    @endphp
                </tr>
            @endforeach
            @if ($currentTypeEducation !== null)
                <tr style="font-weight: bold; background-color: #f2f2f2; text-align: center;">
                    <td colspan="2" style="text-align: center; padding: 12px; font-size: 16px; background-color: #e9ecef;">Subtotal {{ $currentTypeEducation }}</td>
                    <td style="text-align: center; padding: 12px; font-size: 16px; background-color: #e9ecef;">{{ $subtotals['initial']['male'] }}</td>
                    <td style="text-align: center; padding: 12px; font-size: 16px; background-color: #e9ecef;">{{ $subtotals['initial']['female'] }}</td>
                    <td style="text-align: center; padding: 12px; font-size: 16px; background-color: #e9ecef;">{{ $subtotals['initial']['total'] }}</td>
                    <td style="text-align: center; padding: 12px; font-size: 16px; background-color: #e9ecef;">{{ $subtotals['new_entries']['male'] }}</td>
                    <td style="text-align: center; padding: 12px; font-size: 16px; background-color: #e9ecef;">{{ $subtotals['new_entries']['female'] }}</td>
                    <td style="text-align: center; padding: 12px; font-size: 16px; background-color: #e9ecef;">{{ $subtotals['new_entries']['total'] }}</td>
                    <td style="text-align: center; padding: 12px; font-size: 16px; background-color: #e9ecef;">{{ $subtotals['withdrawals']['male'] }}</td>
                    <td style="text-align: center; padding: 12px; font-size: 16px; background-color: #e9ecef;">{{ $subtotals['withdrawals']['female'] }}</td>
                    <td style="text-align: center; padding: 12px; font-size: 16px; background-color: #e9ecef;">{{ $subtotals['withdrawals']['total'] }}</td>
                    <td style="text-align: center; padding: 12px; font-size: 16px; background-color: #e9ecef;">{{ $subtotals['current']['male'] }}</td>
                    <td style="text-align: center; padding: 12px; font-size: 16px; background-color: #e9ecef;">{{ $subtotals['current']['female'] }}</td>
                    <td style="text-align: center; padding: 12px; font-size: 16px; background-color: #e9ecef;">{{ $subtotals['current']['total'] }}</td>
                </tr>
            @endif
            <tr style="font-weight: bold; background-color: #f2f2f2; text-align: center;">
                <td colspan="2" style="text-align: center; padding: 12px; font-size: 16px;background-color: #e9ecef; text-align: center;">Totales</td>
                <td style="text-align: center; padding: 12px; font-size: 16px;background-color: #e9ecef;">{{ $totals['initial']['male'] }}</td>
                <td style="text-align: center; padding: 12px; font-size: 16px;background-color: #e9ecef;">{{ $totals['initial']['female'] }}</td>
                <td style="text-align: center; padding: 12px; font-size: 16px;background-color: #e9ecef;">{{ $totals['initial']['total'] }}</td>
                <td style="text-align: center; padding: 12px; font-size: 16px;background-color: #e9ecef;">{{ $totals['new_entries']['male'] }}</td>
                <td style="text-align: center; padding: 12px; font-size: 16px;background-color: #e9ecef;">{{ $totals['new_entries']['female'] }}</td>
                <td style="text-align: center; padding: 12px; font-size: 16px;background-color: #e9ecef;">{{ $totals['new_entries']['total'] }}</td>
                <td style="text-align: center; padding: 12px; font-size: 16px;background-color: #e9ecef;">{{ $totals['withdrawals']['male'] }}</td>
                <td style="text-align: center; padding: 12px; font-size: 16px;background-color: #e9ecef;">{{ $totals['withdrawals']['female'] }}</td>
                <td style="text-align: center; padding: 12px; font-size: 16px;background-color: #e9ecef;">{{ $totals['withdrawals']['total'] }}</td>
                <td style="text-align: center; padding: 12px; font-size: 16px;background-color: #e9ecef;">{{ $totals['current']['male'] }}</td>
                <td style="text-align: center; padding: 12px; font-size: 16px;background-color: #e9ecef;">{{ $totals['current']['female'] }}</td>
                <td style="text-align: center; padding: 12px; font-size: 16px;background-color: #e9ecef;">{{ $totals['current']['total'] }}</td>
            </tr>
        </tbody>
        
    </table>
</div>

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