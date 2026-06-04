<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 11px; }
        .header { display: table; width: 100%; margin-bottom: 16px; }
        .logo, .title { display: table-cell; vertical-align: middle; }
        .logo { width: 90px; }
        .logo img { width: 70px; }
        h1 { margin: 0; font-size: 20px; text-transform: uppercase; text-align: center; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .meta td { border: 1px solid #cbd5e1; padding: 6px; }
        .meta strong { color: #0f172a; }
        .grades { width: 100%; border-collapse: collapse; }
        .grades th { background: #e2f5ef; color: #064e3b; font-weight: bold; }
        .grades th, .grades td { border: 1px solid #cbd5e1; padding: 6px; }
        .right { text-align: right; }
        .center { text-align: center; }
        .footer { display: table; width: 100%; margin-top: 28px; }
        .signature { display: table-cell; width: 33%; text-align: center; padding-top: 28px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            @if ($logoDataUri)
                <img src="{{ $logoDataUri }}" alt="OFPPT">
            @endif
        </div>
        <div class="title">
            <h1>Procès-verbal des notes</h1>
            <div class="center">Smart Campus OFPPT</div>
        </div>
    </div>

    <table class="meta">
        <tr>
            <td><strong>Etablissement:</strong> {{ $meta['etablissement'] }}</td>
            <td><strong>Filière:</strong> {{ $meta['filiere'] }}</td>
            <td><strong>Groupe:</strong> {{ $meta['groupe'] }}</td>
        </tr>
        <tr>
            <td><strong>Niveau:</strong> {{ $meta['niveau'] }}</td>
            <td><strong>Année de formation:</strong> {{ $meta['annee'] }}</td>
            <td><strong>Module:</strong> {{ $meta['module'] }}</td>
        </tr>
        <tr>
            <td><strong>Inscrits:</strong> {{ $meta['inscrits'] }}</td>
            <td><strong>Présents:</strong> {{ $meta['presents'] }}</td>
            <td><strong>Absents:</strong> {{ $meta['absents'] }}</td>
        </tr>
    </table>

    <table class="grades">
        <thead>
            <tr>
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
                    <td class="right">{{ $row->moy_cc !== null ? number_format((float) $row->moy_cc, 2) : '-' }}</td>
                    <td class="right">{{ $row->efm !== null ? number_format((float) $row->efm, 2) : '-' }}</td>
                    <td class="right">{{ $row->moy_module !== null ? number_format((float) $row->moy_module, 2) : '-' }}</td>
                    <td>{{ $row->getAttribute('observations_text') ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="center">Aucune note pour ces filtres.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <div class="signature">Formateur(s)</div>
        <div class="signature">Directeur Pédagogique</div>
        <div class="signature">Directeur d'EFP<br>Fait à __________ le {{ $meta['date'] }}</div>
    </div>
</body>
</html>
