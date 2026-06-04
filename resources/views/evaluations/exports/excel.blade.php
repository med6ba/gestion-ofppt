<html>
<head>
    <meta charset="utf-8">
</head>
<body>
    <table>
        <tr>
            <th colspan="8" style="font-size:18px;">Procès-verbal des notes - Smart Campus OFPPT</th>
        </tr>
        <tr><td><strong>Etablissement</strong></td><td>{{ $meta['etablissement'] }}</td><td><strong>Filière</strong></td><td>{{ $meta['filiere'] }}</td><td><strong>Groupe</strong></td><td>{{ $meta['groupe'] }}</td></tr>
        <tr><td><strong>Niveau</strong></td><td>{{ $meta['niveau'] }}</td><td><strong>Année</strong></td><td>{{ $meta['annee'] }}</td><td><strong>Module</strong></td><td>{{ $meta['module'] }}</td></tr>
        <tr><td><strong>Inscrits</strong></td><td>{{ $meta['inscrits'] }}</td><td><strong>Présents</strong></td><td>{{ $meta['presents'] }}</td><td><strong>Absents</strong></td><td>{{ $meta['absents'] }}</td></tr>
    </table>
    <br>
    <table border="1">
        <thead>
            <tr style="background:#e2f5ef;">
                <th>CEF</th>
                <th>Nom & Prénom</th>
                <th>Groupe</th>
                <th>Module</th>
                <th>Moy CC /20</th>
                <th>EFM /40</th>
                <th>Moy Module /20</th>
                <th>Observations</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->stagiaire->registration_number }}</td>
                    <td>{{ $row->stagiaire->name }}</td>
                    <td>{{ $row->group->code }}</td>
                    <td>{{ $row->module->name }}</td>
                    <td>{{ $row->moy_cc !== null ? number_format((float) $row->moy_cc, 2) : '-' }}</td>
                    <td>{{ $row->efm !== null ? number_format((float) $row->efm, 2) : '-' }}</td>
                    <td>{{ $row->moy_module !== null ? number_format((float) $row->moy_module, 2) : '-' }}</td>
                    <td>{{ $row->getAttribute('observations_text') ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="8">Aucune note pour ces filtres.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
