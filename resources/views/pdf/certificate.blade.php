<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Certificado — {{ $name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1a1a1a; }
        .frame { border: 6px double #b45309; padding: 50px; text-align: center; margin: 10px; }
        .eyebrow { letter-spacing: 4px; text-transform: uppercase; color: #b45309; font-size: 13px; margin-bottom: 30px; }
        .title { font-size: 20px; color: #444; margin-bottom: 6px; }
        .name { font-size: 34px; font-weight: bold; margin: 20px 0; }
        .placement { font-size: 22px; color: #b45309; font-weight: bold; margin-bottom: 24px; }
        .details { font-size: 14px; color: #555; margin-bottom: 40px; }
        .footer { margin-top: 60px; font-size: 12px; color: #777; }
    </style>
</head>
<body>
    <div class="frame">
        <div class="eyebrow">Certificado Oficial · Ping Masters</div>
        <div class="title">Se otorga el presente certificado a</div>
        <div class="name">{{ $name }}</div>
        <div class="placement">{{ $placement }}</div>
        <div class="details">
            {{ $division->name }} — {{ $tournament->name }}<br>
            {{ $tournament->venue }} {{ $tournament->city }}
        </div>
        <div class="footer">{{ $date }}</div>
    </div>
</body>
</html>
