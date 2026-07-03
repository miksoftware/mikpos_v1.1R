<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=72mm">
    <title>Precuenta — {{ $cuenta->mesa->name ?? 'Mesa' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: 72mm auto; margin: 0mm; }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            font-weight: bold;
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
        .header h1 { font-size: 15px; text-transform: uppercase; letter-spacing: 1px; }
        .header .branch-name { font-size: 13px; font-weight: bold; margin-bottom: 2px; }
        .header .branch-info { font-size: 9px; font-weight: normal; }
        .non-fiscal {
            text-align: center;
            font-size: 9px;
            font-weight: normal;
            font-style: italic;
            background: #f0f0f0;
            padding: 3px;
            margin-bottom: 6px;
            border: 1px dashed #999;
        }
        .meta {
            font-size: 10px;
            margin-bottom: 6px;
            border-bottom: 1px dashed #000;
            padding-bottom: 5px;
        }
        .meta table { width: 100%; }
        .meta td { padding: 1px 0; font-weight: normal; }
        .meta td:last-child { text-align: right; font-weight: bold; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .items-table th {
            font-size: 9px;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            padding: 2px 0;
            text-align: left;
        }
        .items-table th:last-child,
        .items-table td:last-child { text-align: right; }
        .items-table td {
            padding: 2px 0;
            font-size: 10px;
            font-weight: normal;
            border-bottom: 1px dotted #ccc;
        }
        .item-name-cell { max-width: 30mm; }
        .totals-table { width: 100%; border-collapse: collapse; border-top: 1px solid #000; padding-top: 4px; }
        .totals-table td { padding: 2px 0; font-size: 11px; font-weight: normal; }
        .totals-table td:last-child { text-align: right; font-weight: bold; }
        .totals-table tr.grand-total td {
            font-size: 14px;
            font-weight: bold;
            padding-top: 4px;
            border-top: 2px solid #000;
        }
        .persons-line {
            font-size: 10px;
            font-weight: normal;
            text-align: center;
            margin: 4px 0;
        }
        .footer {
            margin-top: 8px;
            text-align: center;
            font-size: 9px;
            font-weight: normal;
            color: #555;
            border-top: 1px dashed #000;
            padding-top: 4px;
        }
        @media print { body { padding: 2mm 1mm; } }
    </style>
</head>
<body>
    <div class="header">
        @if(isset($cuenta->branch))
        <div class="branch-name">{{ $cuenta->branch->name ?? 'MikPOS' }}</div>
        @if($cuenta->branch->address ?? null)
        <div class="branch-info">{{ $cuenta->branch->address }}</div>
        @endif
        @if($cuenta->branch->phone ?? null)
        <div class="branch-info">Tel: {{ $cuenta->branch->phone }}</div>
        @endif
        @else
        <div class="branch-name">MikPOS</div>
        @endif
        <h1>PRECUENTA</h1>
    </div>

    <div class="non-fiscal">⚠ NO ES UN DOCUMENTO FISCAL ⚠</div>

    <div class="meta">
        <table>
            <tr>
                <td>Mesa:</td>
                <td>{{ $cuenta->mesa->name ?? '—' }}
                    @if($cuenta->mesa?->sector) · {{ $cuenta->mesa->sector->name }} @endif
                </td>
            </tr>
            <tr>
                <td>Mesero:</td>
                <td>{{ $cuenta->user->name ?? '—' }}</td>
            </tr>
            <tr>
                <td>Fecha:</td>
                <td>{{ now()->format('d/m/Y H:i') }}</td>
            </tr>
        </table>
    </div>

    {{-- Items --}}
    <table class="items-table">
        <thead>
            <tr>
                <th>Cant</th>
                <th>Descripción</th>
                <th>P.U.</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $subtotalAcc = 0;
                $taxAcc = 0;
                $aggregatedItems = [];
                foreach($cuenta->items as $item) {
                    $key = $item->item_name . '_' . $item->unit_price;
                    if (!isset($aggregatedItems[$key])) {
                        $aggregatedItems[$key] = clone $item;
                    } else {
                        $aggregatedItems[$key]->quantity += $item->quantity;
                        $aggregatedItems[$key]->subtotal += $item->subtotal;
                        $aggregatedItems[$key]->tax_amount += $item->tax_amount;
                    }
                }
            @endphp
            @foreach($aggregatedItems as $item)
            @php
                $unitTotal = (float)$item->unit_price + ((float)$item->unit_price * (float)$item->tax_rate / 100);
                $lineTotal = round((float)$item->subtotal + (float)$item->tax_amount, 2);
                $subtotalAcc += (float)$item->subtotal;
                $taxAcc += (float)$item->tax_amount;
            @endphp
            <tr>
                <td>{{ (int)$item->quantity }}</td>
                <td class="item-name-cell">{{ $item->item_name }}
                    @if($item->notes)<br><small style="font-weight:normal;font-style:italic;font-size:8px;">{{ $item->notes }}</small>@endif
                </td>
                <td>${{ number_format($unitTotal, 2) }}</td>
                <td>${{ number_format($lineTotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    @php
        $subtotalAcc = round($subtotalAcc, 2);
        $taxAcc = round($taxAcc, 2);
        $grandTotal = round($subtotalAcc + $taxAcc, 2);
    @endphp
    <table class="totals-table">
        <tr>
            <td>Subtotal</td>
            <td>${{ number_format($subtotalAcc, 2) }}</td>
        </tr>
        @if($taxAcc > 0)
        <tr>
            <td>Impuestos</td>
            <td>${{ number_format($taxAcc, 2) }}</td>
        </tr>
        @endif
        <tr class="grand-total">
            <td>TOTAL</td>
            <td>${{ number_format($grandTotal, 2) }}</td>
        </tr>
    </table>

    @if(($cuenta->num_persons ?? 1) > 1)
    <div class="persons-line">
        {{ $cuenta->num_persons }} personas ·
        ${{ number_format($grandTotal / $cuenta->num_persons, 2) }} por persona
    </div>
    @endif

    <div class="footer">
        Generado: {{ now()->format('H:i:s') }} — Este documento no tiene valor fiscal
    </div>

    <script>
        window.onload = function() { window.print(); };
    </script>
</body>
</html>
