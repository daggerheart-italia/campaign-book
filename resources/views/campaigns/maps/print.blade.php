<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Stampa Mappa - {{ $map->title }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Georgia, serif; margin: 0; padding: 16px; color: #111; background: #fff; }
        h1 { font-size: 20px; margin: 0 0 12px; }
        .toolbar { display: flex; gap: 8px; margin-bottom: 12px; }
        .toolbar button { padding: 6px 12px; border: 1px solid #ccc; background: #f5f5f5; cursor: pointer; border-radius: 6px; }
        .map-wrap { position: relative; display: inline-block; width: 100%; }
        .map-wrap img { width: 100%; height: auto; display: block; }
        .pin { position: absolute; transform: translate(-50%, -100%); width: 26px; height: 26px; border-radius: 50%; border: 2px solid #fff; color: #fff; font-size: 12px; font-weight: bold; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,.4); }
        .mode-bw .pin { background-color: #000 !important; }
        .mode-bw .legend .dot { background-color: #000 !important; }
        .legend { margin-top: 16px; }
        .legend { font-size: 14px; line-height: 1.6; }
        .legend h2 { font-size: 16px; margin: 8px 0 8px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        .legend ol { padding-left: 22px; margin: 0; }
        .legend li { margin-bottom: 8px; }
        .legend strong { font-weight: 700; }
        .legend .dot { display: inline-block; width: 12px; height: 12px; border-radius: 50%; margin-right: 6px; vertical-align: middle; }
        @media print {
            .toolbar { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <h1>{{ $map->title }}</h1>
    <div class="toolbar print:hidden">
        <button type="button" onclick="document.body.classList.toggle('mode-bw')">Colori / B/N</button>
        <button type="button" onclick="window.print()">Stampa</button>
    </div>
    <div class="map-wrap">
        <img src="{{ $map->image_url }}" alt="{{ $map->title }}">
        @foreach($pins as $i => $pin)
            <div class="pin" style="left: {{ $pin->x }}%; top: {{ $pin->y }}%; background-color: {{ $pin->color ?: '#e11d48' }};">
                {{ $i + 1 }}
            </div>
        @endforeach
    </div>
    <div class="legend">
        <h2>Punti segnati</h2>
        <ol>
            @foreach($pins as $i => $pin)
                <li>
                    <span class="dot" style="background-color: {{ $pin->color ?: '#e11d48' }}"></span>
                    <strong>{{ $pin->title }}</strong>
                    @if($pin->description)
                        — {{ $pin->description }}
                    @endif
                </li>
            @endforeach
        </ol>
    </div>
</body>
</html>
