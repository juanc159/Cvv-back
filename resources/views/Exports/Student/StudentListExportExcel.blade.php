<div>
    <table>
        <thead>
            <tr>
                <th>Funcionario</th>
                <th>Número de documento</th>
                <th>Cargo	</th>
                <th>Estado	</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $row)
                <tr class="">
                    <td>{{ $row['grade_name'] }}</td>
                    <td>{{ $row['section_name'] }}</td>
                    <td>{{ $row['identity_document'] }}</td>
                    <td>{{ $row['full_name'] }}</td>
                    <td>{{ $row['type_education_name'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
