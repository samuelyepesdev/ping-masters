<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cronograma — {{ $tournament->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; }
        h1 { font-size: 18px; margin: 0 0 2px; }
        h2 { font-size: 13px; margin: 16px 0 6px; }
        .subtitle { color: #666; margin: 0 0 16px; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th, td { border: 1px solid #ddd; padding: 4px 6px; text-align: left; font-size: 10px; }
        th { background: #f3f3f3; }
    </style>
</head>
<body>
    <h1>{{ $tournament->name }}</h1>
    <p class="subtitle">Cronograma — {{ $tournament->venue }} {{ $tournament->city }} · {{ $tournament->start_date }} a {{ $tournament->end_date }}</p>

    @foreach($divisions as $division)
        <h2>{{ $division['name'] }}</h2>
        <table>
            <thead>
                <tr>
                    <th>Fase</th>
                    <th>Jugador 1</th>
                    <th>Jugador 2</th>
                    <th>Mesa</th>
                    <th>Horario</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($division['matches'] as $match)
                    <tr>
                        <td>{{ $match['round'] }}</td>
                        <td>{{ $match['entrant1'] }}</td>
                        <td>{{ $match['entrant2'] }}</td>
                        <td>{{ $match['table_number'] ?? '—' }}</td>
                        <td>{{ $match['scheduled_at'] ?? '—' }}</td>
                        <td>{{ $match['status'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
</body>
</html>
