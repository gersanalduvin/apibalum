<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }

        h2 {
            margin: 0 0 8px 0;
            font-size: 16px;
            color: #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 4px 6px;
            text-align: right;
        }

        th.alumno,
        td.alumno {
            text-align: left;
        }

        .grupo-block {
            page-break-after: always;
        }

        .summary-block {
            padding-top: 20px;
        }

        .grupo-block:last-child {
            page-break-after: avoid;
        }

        .totales {
            font-weight: bold;
        }

        /* Nested Header Table Styles */
        .header-row th {
            border: none !important;
            padding: 0 0 10px 0 !important;
            margin: 0 !important;
            background-color: #fff !important;
        }

        .header-table {
            width: 100%;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 5px;
            border-collapse: collapse;
            border: none !important;
        }

        .header-logo-td {
            width: 100px;
            text-align: left;
            vertical-align: middle;
            border: none !important;
        }

        .header-logo-td img {
            width: 90px;
            height: auto;
            max-height: 90px;
        }

        .header-content-td {
            text-align: center;
            vertical-align: middle;
            padding-right: 100px;
            /* Balance the logo on the left */
            border: none !important;
        }

        .header-title {
            font-weight: bold;
            font-size: 18px;
            color: #000;
            margin-bottom: 5px;
        }

        .header-subtitle {
            font-weight: bold;
            font-size: 14px;
            color: #000;
            margin-top: 2px;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        tr {
            page-break-inside: avoid;
        }
    </style>
    <title>Cuentas x Cobrar</title>
</head>

<body>
    @foreach($grupos as $index => $g)
    <div class="grupo-block">
        <table>
            <thead>
                <tr class="header-row">
                    <th colspan="{{ count($meses_cols) + 2 }}">
                        @include('pdf.header_content', [
                            'perfil' => ($g['formato'] ?? 'cuantitativo') === 'cualitativo' ? 'cualitativo' : 'cuantitativo',
                            'titulo' => $titulo ?? 'Cuentas x Cobrar',
                            'subtitulo1' => $subtitulo1 ?? '',
                            'subtitulo2' => ($subtitulo2 ?? '') . ($subtitulo2 ? ' | ' : '') . ($g['grupo_nombre'] ?: 'Grupo')
                        ])
                    </th>
                </tr>
                <tr>
                    <th class="alumno">Alumno</th>

                    @foreach($meses_cols as $col)
                    <th>{{ strtoupper($col) }}</th>
                    @endforeach
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($g['rows'] as $row)
                <tr>
                    <td class="alumno">{{ $row['alumno'] }}</td>

                    @foreach($meses_cols as $col)
                    <td>{{ is_numeric($row[$col]) ? number_format($row[$col], 2) : $row[$col] }}</td>
                    @endforeach
                    <td>{{ is_numeric($row['total']) ? number_format($row['total'], 2) : $row['total'] }}</td>
                </tr>
                @endforeach
                <tr class="totales">
                    <td class="alumno">Totales</td>

                    @foreach($meses_cols as $col)
                    <td>{{ is_numeric($g['totales_por_mes'][$col] ?? '') ? number_format($g['totales_por_mes'][$col], 2) : ($g['totales_por_mes'][$col] ?? '') }}</td>
                    @endforeach
                    <td>{{ is_numeric($g['total_general'] ?? '') ? number_format($g['total_general'], 2) : ($g['total_general'] ?? '') }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endforeach

    <div class="summary-block">
        <h2>Resumen General</h2>
        <table>
            <thead>
                <tr>
                    @foreach($meses_cols as $col)
                    <th>{{ strtoupper($col) }}</th>
                    @endforeach
                    <th>Total General</th>
                </tr>
            </thead>
            <tbody>
                <tr class="totales">
                    @foreach($meses_cols as $col)
                    <td>{{ is_numeric($resumen_global['totales_por_mes'][$col] ?? '') ? number_format($resumen_global['totales_por_mes'][$col], 2) : ($resumen_global['totales_por_mes'][$col] ?? '') }}</td>
                    @endforeach
                    <td>{{ is_numeric($resumen_global['total_general'] ?? '') ? number_format($resumen_global['total_general'], 2) : ($resumen_global['total_general'] ?? '') }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</body>

</html>