<table class="table table-bordered">
    <thead>
        <tr>
            <th>Tipo Educación</th>
            <th>Grado y Sección</th>
            <th colspan="3">Matrícula Inicial</th>
            <th colspan="3">Nuevos Ingresos</th>
            <th colspan="3">Egresos</th>
            <th colspan="3">Matrícula Actual</th>
        </tr>
        <tr>
            <th></th>
            <th></th>
            <th>M</th>
            <th>F</th>
            <th>T</th>
            <th>M</th>
            <th>F</th>
            <th>T</th>
            <th>M</th>
            <th>F</th>
            <th>T</th>
            <th>M</th>
            <th>F</th>
            <th>T</th>
        </tr>
    </thead>
    <tbody>
        @foreach($statistics as $stat)
        <tr>
            <td>{{ $stat['type_education_name'] }}</td>
            <td>{{ $stat['grade_name'] }} - {{ $stat['section_name'] }}</td>
            
            <!-- Matrícula Inicial -->
            <td>{{ $stat['initial']['male'] }}</td>
            <td>{{ $stat['initial']['female'] }}</td>
            <td>{{ $stat['initial']['total'] }}</td>
            
            <!-- Nuevos Ingresos -->
            <td>{{ $stat['new_entries']['male'] }}</td>
            <td>{{ $stat['new_entries']['female'] }}</td>
            <td>{{ $stat['new_entries']['total'] }}</td>
            
            <!-- Egresos -->
            <td>{{ $stat['withdrawals']['male'] }}</td>
            <td>{{ $stat['withdrawals']['female'] }}</td>
            <td>{{ $stat['withdrawals']['total'] }}</td>
            
            <!-- Matrícula Actual -->
            <td>{{ $stat['current']['male'] }}</td>
            <td>{{ $stat['current']['female'] }}</td>
            <td>{{ $stat['current']['total'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>