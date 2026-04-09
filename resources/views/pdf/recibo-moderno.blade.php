<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Recibo de Pago - {{ $recibo->numero_recibo }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 15px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            padding: 10px;
        }
        .info-section {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
        }
        .info-section td {
            padding: 4px;
            vertical-align: top;
        }
        .label {
            font-weight: bold;
            color: #555;
            width: 110px;
        }
        .value {
            border-bottom: 1px dotted #ccc;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            text-transform: uppercase;
            font-size: 14px;
        }
        .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        .text-right {
            text-align: right;
        }
        .totals-section {
            width: 100%;
            margin-top: 10px;
        }
        .amount-words {
            font-style: italic;
            margin-bottom: 25px;
            padding: 10px;
            font-size: 15px;
            background-color: #fafafa;
            border-left: 4px solid #ddd;
        }
        .signatures {
            margin-top: 50px;
            width: 100%;
        }
        .signature-box {
            width: 45%;
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 5px;
        }
        .receipt-footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #777;
        }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            color: rgba(0, 0, 0, 0.05);
            z-index: -1;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="container">
        @if($recibo->estado === 'anulado')
            <div class="watermark">ANULADO</div>
        @endif

        @include('pdf.header_content', [
            'perfil' => $perfil,
            'titulo' => 'RECIBO OFICIAL DE CAJA',
            'subtitulo1' => 'N°: ' . str_pad($recibo->numero_recibo, 6, '0', STR_PAD_LEFT),
            'subtitulo2' => 'Fecha: ' . ($recibo->fecha ? $recibo->fecha->format('d/m/Y') : now()->format('d/m/Y'))
        ])

        <table class="info-section">
            <tr>
                <td class="label">Recibimos de:</td>
                <td class="value" colspan="3">{{ $recibo->usuario->nombre_completo ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Grado:</td>
                <td class="value">{{ $recibo->grado ?? 'N/A' }}</td>
                <td class="label" style="text-align: right;">Sección:</td>
                <td class="value">{{ $recibo->seccion ?? 'N/A' }}</td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Concepto</th>
                    <th style="width: 10%;" class="text-right">Cant.</th>
                    <th style="width: 20%;" class="text-right">Monto</th>
                    <th style="width: 20%;" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recibo->detalles as $detalle)
                <tr>
                    <td>{{ $detalle->concepto }}</td>
                    <td class="text-right">{{ number_format($detalle->cantidad, 0) }}</td>
                    <td class="text-right">{{ number_format($detalle->monto, 2) }}</td>
                    <td class="text-right">{{ number_format($detalle->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right" style="font-weight: bold; border: none; padding-top: 5px;">TOTAL:</td>
                    <td class="text-right" style="font-weight: bold; font-size: 17px; border: 1px solid #ddd; padding-top: 5px;">
                        C$ {{ number_format($recibo->total, 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>

        <div class="amount-words">
            <strong>Total en letras:</strong> {{ $cantidad_letras }}
        </div>

        <table class="signatures">
            <tr>
                <td class="signature-box">Entregué Conforme</td>
                <td style="width: 15%;"></td>
                <td class="signature-box">Recibí Conforme</td>
            </tr>
        </table>
    </div>
</body>
</html>
