 @foreach ($data as $index => $pendingRegistration)
     <table>
         <tr>
             <td colspan="5" style="text-align: center">
                 <b>{{ $pendingRegistration['company_name'] ?? 'N/A' }}</b>
             </td>
         </tr>


         <tr>
             <td colspan="5" style="text-align: center">
                 <b>Año Escolar: {{ $pendingRegistration['term_name'] ?? 'N/A' }}</b>
             </td>
         </tr>

         <tr>

             <td colspan="5" style="text-align: center">
                 <b>Fecha: {{ now()->format('d/m/Y') }} </b>
             </td>

         </tr>
         <tr>

             <td colspan="5" style="text-align: center">
                 <b>Reporte de materia pendiente</b>
             </td>
         </tr>
         <tr>

             <td colspan="5" style="text-align: center">
                 <b> Sección: {{ $pendingRegistration['section_name'] }}</b>
             </td>
         </tr>
         <tr>

         </tr>

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
         @endphp

         <tr>
             <th style="text-align: center">No.</th>
             <th style="text-align: center">ALUMNO</th>
             @foreach ($selectedSubjects as $subject)
                 <th style="text-align: center">{{ $subject['title'] }}</th>
             @endforeach
         </tr>
         <tbody>
             @forelse($pendingRegistration['students'] as $indexStudent => $student)
                 <tr>
                     <td>{{ $indexStudent + 1 }}</td>
                     <td>{{ $student['student_id']['title'] ?? 'N/A' }}</td>
                     @foreach ($selectedSubjects as $subject)
                         @php
                             // Replicar getAttemptsByStudentSubject
                             $studentAttempts = $attempts
                                 ->where('student_id.value', $student['student_id']['value'])
                                 ->where('subject_id.value', $subject['value'])
                                 ->sortByDesc('attempt_number') // Ordenar de mayor a menor
                                 ->values();

                             // Replicar shouldShowAddButton (solo para lógica visual en Excel)
                             $hasSubject = collect($student['subjects'])->contains('id', $subject['value']);
                             $showAddIndicator =
                                 $studentAttempts->count() < 4 &&
                                 ($studentAttempts->isEmpty() || !$studentAttempts->first()['approved']);
                         @endphp
                         <td>
                             @if ($hasSubject)
                                 @forelse($studentAttempts as $attempt)
                                     <div>
                                         Momento {{ $attempt['attempt_number'] }}:
                                         {{ $attempt['note'] }}
                                         ({{ $attempt['attempt_date'] }})
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
                         <td
                             style="text-align: center; padding: 12px; border-right: 1px solid #ddd; background-color: #c8e6c9;">
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
                     <td colspan="{{ count($selectedSubjects) + 2 }}" style="background-color: #acadac;"></td>
                 </tr>
             </table>
         @endif
     @endforeach
