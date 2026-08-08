<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Llave — {{ $division->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; }
        h1 { font-size: 18px; margin: 0 0 2px; }
        h2 { font-size: 14px; margin: 18px 0 8px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        .subtitle { color: #666; margin: 0 0 16px; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #ddd; padding: 4px 6px; text-align: left; font-size: 10px; }
        th { background: #f3f3f3; }
        .match-table td.winner { font-weight: bold; }
        .round-block { page-break-inside: avoid; margin-bottom: 14px; }
        .round-title { font-weight: bold; font-size: 11px; margin-bottom: 4px; }
        .score-col { width: 70px; text-align: center; }
    </style>
</head>
<body>
    <h1>{{ $tournament->name }}</h1>
    <p class="subtitle">{{ $division->name }} — {{ $tournament->venue }} {{ $tournament->city }}</p>

    @if($groups->isNotEmpty())
        <h2>Fase de grupos</h2>
        @foreach($groups as $group)
            <div class="round-block">
                <div class="round-title">{{ $group['name'] }}</div>
                <table>
                    <thead>
                        <tr><th>#</th><th>Jugador</th><th>PJ</th><th>G</th><th>P</th><th>Pts</th></tr>
                    </thead>
                    <tbody>
                        @foreach($group['standings'] as $i => $row)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $row['name'] }}</td>
                                <td>{{ $row['played'] }}</td>
                                <td>{{ $row['wins'] }}</td>
                                <td>{{ $row['losses'] }}</td>
                                <td>{{ $row['points'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <table class="match-table">
                    @foreach($group['matches'] as $match)
                        <tr>
                            <td class="{{ $match['winner'] === 1 ? 'winner' : '' }}">{{ $match['entrant1'] }}</td>
                            <td class="score-col">{{ $match['score'] ?? 'vs' }}</td>
                            <td class="{{ $match['winner'] === 2 ? 'winner' : '' }}">{{ $match['entrant2'] }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endforeach
    @endif

    @if($divisionStandings !== null)
        <h2>Tabla de posiciones</h2>
        <table>
            <thead>
                <tr><th>#</th><th>Jugador</th><th>PJ</th><th>G</th><th>P</th><th>Pts</th></tr>
            </thead>
            <tbody>
                @foreach($divisionStandings as $i => $row)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['played'] }}</td>
                        <td>{{ $row['wins'] }}</td>
                        <td>{{ $row['losses'] }}</td>
                        <td>{{ $row['points'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>{{ $groups->isNotEmpty() ? 'Fase eliminatoria' : 'Partidos' }}</h2>
    @foreach($rounds as $round)
        <div class="round-block">
            <div class="round-title">{{ $round['title'] }}</div>
            <table class="match-table">
                @foreach($round['matches'] as $match)
                    <tr>
                        <td class="{{ $match['winner'] === 1 ? 'winner' : '' }}">{{ $match['entrant1'] }}</td>
                        <td class="score-col">{{ $match['score'] ?? 'vs' }}</td>
                        <td class="{{ $match['winner'] === 2 ? 'winner' : '' }}">{{ $match['entrant2'] }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endforeach
</body>
</html>
