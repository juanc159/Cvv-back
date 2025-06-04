<div>
    <table>
        <thead>
            <tr>
                <th>Tipo de educación</th>
                <th>Grado / Nivel</th>
                <th>Sección</th>
                <th>País</th>
                <th>Estado</th>
                <th>Municipio</th>
                <th>Nacionalizado</th>
                <th>Tipo de documento</th>
                <th>Documento</th>
                <th>Nombre completo</th>
                <th>Sexo</th>
                <th>Fecha de nacimiento</th>
                <th>Foto</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $row)
                <tr>
                    <td>{{ $row['type_education_name'] }}</td>
                    <td>{{ $row['grade_name'] }}</td>
                    <td>{{ $row['section_name'] }}</td>
                    <td>{{ $row['country_name'] }}</td>
                    <td>{{ $row['state_name'] }}</td>
                    <td>{{ $row['city_name'] }}</td>
                    <td>{{ $row['nationalized'] }}</td>
                    <td>{{ $row['type_document_name'] }}</td>
                    <td>{{ $row['identity_document'] }}</td>
                    <td>{{ $row['full_name'] }}</td>
                    <td>{{ $row['gender'] }}</td>
                    <td>{{ $row['birthday'] }}</td>
                    <td>{{ $row['photo'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
