<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=72mm">
    <title>Comanda — {{ $cuenta->mesa->name ?? 'Mesa' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: 72mm auto; margin: 0mm; }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            width: 72mm;
            max-width: 72mm;
            padding: 3mm 2mm;
            background: #fff;
            color: #000;
            -webkit-print-color-adjust: exact;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
            margin-bottom: 6px;
        }
        .header h1 {
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header p { font-size: 10px; margin-top: 2px; }
        .meta {
            font-size: 10px;
            margin-bottom: 6px;
            border-bottom: 1px dashed #000;
            padding-bottom: 5px;
        }
        .meta table { width: 100%; }
        .meta td { padding: 1px 0; }
        .meta td:last-child { text-align: right; font-weight: bold; }
        .station {
            margin-bottom: 8px;
        }
        .station-title {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            background: #000;
            color: #fff;
            padding: 2px 4px;
            margin-bottom: 4px;
        }
        .item-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
            border-bottom: 1px dotted #ccc;
            font-size: 11px;
        }
        .item-qty {
            font-weight: bold;
            font-size: 13px;
            min-width: 22px;
        }
        .item-name { flex: 1; padding: 0 4px; }
        .item-notes {
            font-size: 9px;
            font-style: italic;
            color: #333;
            padding-left: 26px;
            margin-bottom: 2px;
        }
        .footer {
            margin-top: 8px;
            text-align: center;
            font-size: 9px;
            color: #555;
            border-top: 1px dashed #000;
            padding-top: 4px;
        }
        @media print {
            body { padding: 2mm 1mm; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>COMANDA</h1>
        <p>{{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="meta">
        <table>
            <tr>
                <td>Mesa:</td>
                <td>{{ $cuenta->mesa->name ?? '—' }}</td>
            </tr>
            @if($cuenta->mesa?->sector)
            <tr>
                <td>Sector:</td>
                <td>{{ $cuenta->mesa->sector->name }}</td>
            </tr>
            @endif
            <tr>
                <td>Mesero:</td>
                <td>{{ $cuenta->user->name ?? '—' }}</td>
            </tr>
            <tr>
                <td>Personas:</td>
                <td>{{ $cuenta->num_persons ?? 1 }}</td>
            </tr>
            @if($cuenta->notes)
            <tr>
                <td>Nota mesa:</td>
                <td>{{ $cuenta->notes }}</td>
            </tr>
            @endif
        </table>
    </div>

    @php
        // Group items by preparation station
        $stationGroups = [];
        foreach ($cuenta->items as $item) {
            $ps = $item->preparationStation;
            $key = $ps ? $ps->id : 0;
            if (!isset($stationGroups[$key])) {
                $stationGroups[$key] = [
                    'label' => $ps ? (($ps->icon ? $ps->icon . ' ' : '') . strtoupper($ps->name)) : '📋 SIN ESTACIÓN',
                    'items' => [],
                ];
            }
            $stationGroups[$key]['items'][] = $item;
        }
    @endphp

    @foreach($stationGroups as $group)
        @if(count($group['items']) > 0)
        <div class="station">
            <div class="station-title">{{ $group['label'] }}</div>
            @foreach($group['items'] as $item)
            <div class="item-row">
                <span class="item-qty">{{ (int) $item->quantity }}x</span>
                <span class="item-name">{{ $item->item_name }}</span>
            </div>
            @if($item->notes)
            <div class="item-notes">↳ {{ $item->notes }}</div>
            @endif
            @endforeach
        </div>
        @endif
    @endforeach

    <div class="footer">
        Impreso: {{ now()->format('H:i:s') }}
    </div>

    <script>
        window.onload = function() { window.print(); };
    </script>
</body>
</html>
