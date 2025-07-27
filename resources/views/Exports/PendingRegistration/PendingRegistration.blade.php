@foreach ($data as $index => $pendingRegistration)
    <table style="border-collapse: collapse; width: 100%; margin-bottom: 20px;">
        @php
            // Calcular selectedSubjects como en tu computed
            $subjectMap = [];
            foreach ($pendingRegistration['students'] as $student) {
                foreach ($student['subjects'] as $subject) {
                    if (!isset($subjectMap[$subject['id']])) {
                        $subjectMap[$subject['id']] = [
                            'value' => $subject['id'],
                            'title' => $subject['name'],
                        ];
                    }
                }
            }
            $selectedSubjects = array_values($subjectMap);
            // Calcular el total de columnas: 2 (No. y ALUMNO) + número de materias
            $totalColumns = 2 + count($selectedSubjects);
        @endphp

        <!-- Header Rows (Company, Year, Date, Report Title, Section) -->
        <tr>
            <td colspan="{{ $totalColumns }}" style="text-align: center; background-color: #D6EAF8; padding: 8px;">
                <b>{{ $pendingRegistration['company_name'] ?? 'N/A' }}</b>
            </td>
        </tr>
        <tr>
            <td colspan="{{ $totalColumns }}" style="text-align: center; background-color: #D6EAF8; padding: 8px;">
                <b>Año Escolar: {{ $pendingRegistration['term_name'] ?? 'N/A' }}</b>
            </td>
        </tr>
        <tr>
            <td colspan="{{ $totalColumns }}" style="text-align: center; background-color: #D6EAF8; padding: 8px;">
                <b>Fecha: {{ now()->format('d/m/Y') }}</b>
            </td>
        </tr>
        <tr>
            <td colspan="{{ $totalColumns }}" style="text-align: center; background-color: #D6EAF8; padding: 8px;">
                <b>Reporte de materia pendiente</b>
            </td>
        </tr>
        <tr>
            <td colspan="{{ $totalColumns }}" style="text-align: center; background-color: #D6EAF8; padding: 8px;">
                <b>Sección: {{ $pendingRegistration['section_name'] }}</b>
            </td>
        </tr>
        <tr>
            <td colspan="{{ $totalColumns }}" style="height: 10px;"></td>
        </tr>

        <!-- Table Headers -->
        <tr>
            <th style="text-align: center; background-color: #D6EAF8; padding: 8px; border: 1px solid #ddd;">No.</th>
            <th style="text-align: center; background-color: #FFF9C4; padding: 8px; border: 1px solid #ddd;">ALUMNO</th>
            @foreach ($selectedSubjects as $subject)
                <th style="text-align: center; background-color: #D4EFDF; padding: 8px; border: 1px solid #ddd;">
                    {{ $subject['title'] }}
                </th>
            @endforeach
        </tr>

        <!-- Table Body -->
        <tbody>
            @forelse($pendingRegistration['students'] as $indexStudent => $student)
                <tr>
                    <td style="text-align: center; padding: 8px; border: 1px solid #ddd;">{{ $indexStudent + 1 }}</td>
                    <td style="text-align: center; background-color: #FFF9C4; padding: 8px; border: 1px solid #ddd;">
                        {{ $student['student_id']['title'] ?? 'N/A' }}
                    </td>
                    @foreach ($selectedSubjects as $subject)
                        @php
                            // Replicar getAttemptsByStudentSubject
                            $studentAttempts = $attempts
                                ->where('student_id.value', $student['student_id']['value'])
                                ->where('subject_id.value', $subject['value'])
                                ->sortByDesc('attempt_number')
                                ->values();

                            // Replicar shouldShowAddButton
                            $hasSubject = collect($student['subjects'])->contains('id', $subject['value']);
                            $showAddIndicator =
                                $studentAttempts->count() < 4 &&
                                ($studentAttempts->isEmpty() || !$studentAttempts->first()['approved']);
                        @endphp
                        <td style="text-align: center; padding: 8px; border: 1px solid #ddd; background-color: {{ $hasSubject && $studentAttempts->isNotEmpty() ? '#E8DAEF' : ($hasSubject ? '#FFE5D9' : 'transparent') }};">
                            @if ($hasSubject)
                                @forelse($studentAttempts as $attempt)
                                    <div>
                                        Momento {{ $attempt['attempt_number'] }}:
                                        ({{ $attempt['attempt_date'] }})
                                        {{ $attempt['note'] }}
                                        <br>
                                    </div>
                                @empty
                                    <!-- Sin intentos -->
                                @endforelse
                                @if ($showAddIndicator)
                                    <div style="color: blue;">[Pendiente de agregar intento]</div>
                                @endif
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $totalColumns }}" style="text-align: center; padding: 12px; background-color: #D1F2EB; border: 1px solid #ddd;">
                        No hay estudiantes seleccionados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Separador solo entre pendingRegistrations, no después del último -->
    @if ($index < count($data) - 1)
        <table>
            <tr>
                <td colspan="{{ $totalColumns }}" style="background-color: #E5E7EB; height: 10px;"></td>
            </tr>
        </table>
    @endif
@endforeach