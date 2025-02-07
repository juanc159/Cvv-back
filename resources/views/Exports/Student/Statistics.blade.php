<div class="container" style="margin-top: 20px; font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
    <h2 style="color: #333; font-size: 24px; font-weight: bold; text-align: center; margin-bottom: 20px;">Estadísticas de Estudiantes</h2>
    <table class="table table-bordered" style="border: 2px solid #ccc; border-collapse: collapse; width: 100%; font-size: 14px; border-radius: 8px; overflow: hidden;">
        <thead>
            <tr style="background-color: #f8f9fa; color: #333; font-weight: bold; text-align: center;">
                <th style="border-right: 2px solid #666; padding: 12px; background-color: #e9ecef; font-size: 15px;">Tipo de Educación</th>
                <th style="border-right: 2px solid #666; padding: 12px; background-color: #e9ecef; font-size: 15px;">Grado y Sección</th>
                <th colspan="3" style="background-color: #4199cc; color: white; padding: 12px; font-size: 15px;">Matrícula Inicial</th>
                <th colspan="3" style="border-right: 2px solid #666; background-color: #007bff; color: white; padding: 12px; font-size: 15px;">Ingresos</th>
                <th colspan="3" style="border-right: 2px solid #666; background-color: #dc3545; color: white; padding: 12px; font-size: 15px;">Egresos</th>
                <th colspan="3" style="background-color: #28a745; color: white; padding: 12px; font-size: 15px;">Matrícula Actual</th>
            </tr>
            <tr style="background-color: #f8f9fa; color: #333; font-weight: bold; text-align: center;">
                <th></th>
                <th></th>
                <th style="padding: 10px; font-size: 14px;">M</th>
                <th style="padding: 10px; font-size: 14px;">F</th>
                <th style="padding: 10px; font-size: 14px;">T</th>
                <th style="padding: 10px; font-size: 14px;">M</th>
                <th style="padding: 10px; font-size: 14px;">F</th>
                <th style="padding: 10px; font-size: 14px;">T</th>
                <th style="padding: 10px; font-size: 14px;">M</th>
                <th style="padding: 10px; font-size: 14px;">F</th>
                <th style="padding: 10px; font-size: 14px;">T</th>
                <th style="padding: 10px; font-size: 14px;">M</th>
                <th style="padding: 10px; font-size: 14px;">F</th>
                <th style="padding: 10px; font-size: 14px;">T</th>
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
            @endphp
            @foreach($statistics as $stat)
                <tr style="text-align: center; background-color: #fff; border-bottom: 1px solid #ddd;">
                    @if ($stat['type_education_name'] != $currentTypeEducation)
                        @php
                            $countTypeEducation = collect($statistics)->filter(function($item) use ($stat) {
                                return $item['type_education_name'] === $stat['type_education_name'];
                            })->count();
                            $currentTypeEducation = $stat['type_education_name']; // Actualiza el tipo de educación actual
                        @endphp
                        <td rowspan="{{ $countTypeEducation }}" style="padding: 12px; font-weight: bold; background-color: #f1f1f1; border-right: 1px solid #ddd;">{{ $stat['type_education_name'] }}</td>
                    @endif
                    <td style="padding: 12px; border-right: 1px solid #ddd;">{{ $stat['grade_name'] }} - {{ $stat['section_name'] }}</td>
                    <td style="padding: 12px; border-right: 1px solid #ddd;">{{ $stat['initial']['male'] }}</td>
                    <td style="padding: 12px; border-right: 1px solid #ddd;">{{ $stat['initial']['female'] }}</td>
                    <td style="padding: 12px; border-right: 1px solid #ddd;">{{ $stat['initial']['total'] }}</td>
                    <td style="padding: 12px; border-right: 1px solid #ddd;">{{ $stat['new_entries']['male'] }}</td>
                    <td style="padding: 12px; border-right: 1px solid #ddd;">{{ $stat['new_entries']['female'] }}</td>
                    <td style="padding: 12px; border-right: 1px solid #ddd;">{{ $stat['new_entries']['total'] }}</td>
                    <td style="padding: 12px; border-right: 1px solid #ddd;">{{ $stat['withdrawals']['male'] }}</td>
                    <td style="padding: 12px; border-right: 1px solid #ddd;">{{ $stat['withdrawals']['female'] }}</td>
                    <td style="padding: 12px; border-right: 1px solid #ddd;">{{ $stat['withdrawals']['total'] }}</td>
                    <td style="padding: 12px; border-right: 1px solid #ddd;">{{ $stat['current']['male'] }}</td>
                    <td style="padding: 12px; border-right: 1px solid #ddd;">{{ $stat['current']['female'] }}</td>
                    <td style="padding: 12px;">{{ $stat['current']['total'] }}</td>
                    @php
                        // Acumular los totales
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
                    @endphp
                </tr>
            @endforeach
            <tr style="font-weight: bold; background-color: #f2f2f2; text-align: center;">
                <td colspan="2" style="padding: 12px; font-size: 16px; text-align: center;">Subtotales</td>
                <td style="padding: 12px; font-size: 16px;">{{ $totals['initial']['male'] }}</td>
                <td style="padding: 12px; font-size: 16px;">{{ $totals['initial']['female'] }}</td>
                <td style="padding: 12px; font-size: 16px;">{{ $totals['initial']['total'] }}</td>
                <td style="padding: 12px; font-size: 16px;">{{ $totals['new_entries']['male'] }}</td>
                <td style="padding: 12px; font-size: 16px;">{{ $totals['new_entries']['female'] }}</td>
                <td style="padding: 12px; font-size: 16px;">{{ $totals['new_entries']['total'] }}</td>
                <td style="padding: 12px; font-size: 16px;">{{ $totals['withdrawals']['male'] }}</td>
                <td style="padding: 12px; font-size: 16px;">{{ $totals['withdrawals']['female'] }}</td>
                <td style="padding: 12px; font-size: 16px;">{{ $totals['withdrawals']['total'] }}</td>
                <td style="padding: 12px; font-size: 16px;">{{ $totals['current']['male'] }}</td>
                <td style="padding: 12px; font-size: 16px;">{{ $totals['current']['female'] }}</td>
                <td style="padding: 12px; font-size: 16px;">{{ $totals['current']['total'] }}</td>
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