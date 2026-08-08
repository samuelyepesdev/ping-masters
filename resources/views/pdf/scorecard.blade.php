<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Planilla de partido</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a1a; }
        h1 { font-size: 18px; margin: 0 0 2px; }
        .subtitle { color: #666; margin: 0 0 20px; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        th, td { border: 1px solid #999; padding: 10px; text-align: center; }
        th { background: #f3f3f3; }
        .players { width: 100%; margin-bottom: 20px; }
        .players td { border: none; padding: 8px 0; font-size: 15px; font-weight: bold; }
        .meta { color: #555; font-size: 11px; margin-bottom: 20px; }
        .signatures { width: 100%; margin-top: 60px; }
        .signatures td { border: none; text-align: center; padding-top: 30px; border-top: 1px solid #333; font-size: 11px; }
        .sig-gap { border: none !important; }
    </style>
</head>
<body>
    <h1>Planilla de partido</h1>
    <p class="subtitle">{{ $tournament->name }} — {{ $division->name }}</p>
    <p class="meta">
        Formato: mejor de {{ $division->best_of }} · a {{ $division->points_to_win }} puntos
        @if($match->round) · {{ $match->round->name }} @endif
        @if($match->table_number) · Mesa {{ $match->table_number }} @endif
    </p>

    <table class="players">
        <tr>
            <td style="width: 45%;">{{ $entrant1 }}</td>
            <td style="width: 10%; font-weight: normal; color: #999;">vs</td>
            <td style="width: 45%;">{{ $entrant2 }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Juego</th>
                <th>{{ $entrant1 }}</th>
                <th>{{ $entrant2 }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($games as $game)
                <tr>
                    <td>{{ $game['game_number'] }}</td>
                    <td>{{ $game['entrant1_points'] }}</td>
                    <td>{{ $game['entrant2_points'] }}</td>
                </tr>
            @endforeach
            @for($i = 0; $i < $blankRowsNeeded; $i++)
                <tr>
                    <td>{{ $games->count() + $i + 1 }}</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
            @endfor
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td style="width: 40%;">{{ $entrant1 }}</td>
            <td class="sig-gap" style="width: 20%;"></td>
            <td style="width: 40%;">{{ $entrant2 }}</td>
        </tr>
    </table>
    <table class="signatures">
        <tr>
            <td style="width: 100%;">Árbitro</td>
        </tr>
    </table>
</body>
</html>
