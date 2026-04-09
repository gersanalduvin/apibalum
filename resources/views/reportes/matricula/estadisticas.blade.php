<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas de Matrícula - {{ $periodo_lectivo->nombre }}</title>
    <link rel="stylesheet" href="{{ public_path('css/pdf-global-styles.css') }}">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 15px;
            line-height: 1.3;
        }

        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #2c3e50;
            background-color: #ecf0f1;
            padding: 8px 12px;
            border-left: 4px solid #3498db;
            page-break-after: avoid;
        }

        /* Badges numerados */
        .numero-badge {
            background-color: #007bff;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: bold;
            display: inline-block;
            min-width: 16px;
            text-align: center;
        }

        /* Estilos para las tablas */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
            page-break-inside: avoid;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
            text-align: center;
        }

        td {
            text-align: center;
        }

        td:first-child {
            text-align: left;
        }

        .total-row {
            background-color: #e9ecef;
            font-weight: bold;
            page-break-inside: avoid;
        }

        .total-row td {
            border-top: 2px solid #007bff;
        }

        /* Mejorar saltos de página */
        .page-break {
            page-break-before: always;
        }

        /* Evitar huérfanos y viudas */
        .section {
            orphans: 3;
            widows: 3;
        }

        /* Asegurar que las tablas no se rompan mal */
        thead {
            display: table-header-group;
        }

        tbody {
            display: table-row-group;
        }

        tr {
            page-break-inside: avoid;
        }

        .info-period {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 8px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-size: 10px;
        }
    </style>
</head>

<body>
    <!-- Estadísticas por Grupo y Turno -->
    @if(count(data_get($estadisticas_grupo_turno, 'estadisticas', [])) > 0)
    <div class="section">
        <div class="section-title">ESTADÍSTICAS POR GRUPO Y TURNO</div>
        <table>
            <thead>
                <tr>
                    <th>Grupo</th>
                    <th>Turno</th>
                    <th>Varones</th>
                    <th>Mujeres</th>
                    <th>Nuevo Ingreso</th>
                    <th>Reingreso</th>
                    <th>Traslado</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($estadisticas_grupo_turno['estadisticas'] as $estadistica)
                <tr>
                    <td>{{ $estadistica['grupo'] }}</td>
                    <td>{{ $estadistica['turno'] }}</td>
                    <td>{{ $estadistica['varones'] }}</td>
                    <td>{{ $estadistica['mujeres'] }}</td>
                    <td>{{ $estadistica['nuevos_ingresos'] }}</td>
                    <td>{{ $estadistica['reingresos'] }}</td>
                    <td>{{ $estadistica['traslados'] }}</td>
                    <td><span class="numero-badge">{{ $estadistica['total'] }}</span></td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2"><strong>TOTAL</strong></td>
                    <td><strong>{{ $estadisticas_grupo_turno['totales']['varones'] }}</strong></td>
                    <td><strong>{{ $estadisticas_grupo_turno['totales']['mujeres'] }}</strong></td>
                    <td><strong>{{ $estadisticas_grupo_turno['totales']['nuevos_ingresos'] }}</strong></td>
                    <td><strong>{{ $estadisticas_grupo_turno['totales']['reingresos'] }}</strong></td>
                    <td><strong>{{ $estadisticas_grupo_turno['totales']['traslados'] }}</strong></td>
                    <td><strong><span class="numero-badge">{{ $estadisticas_grupo_turno['totales']['total'] }}</span></strong></td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    <!-- Estadísticas por Grado y Turno -->
    @if(isset($estadisticas_grado_turno) && ($tipo_reporte == 'grado_turno' || $tipo_reporte == 'completo'))
    <div class="section {{ ($tipo_reporte == 'completo' && count(data_get($estadisticas_grupo_turno, 'estadisticas', [])) > 0) ? 'page-break' : '' }}">
        <div class="section-title">ESTADÍSTICAS POR GRADO Y TURNO</div>
        <table>
            <thead>
                <tr>
                    <th>Grado</th>
                    <th>Turno</th>
                    <th>Varones</th>
                    <th>Mujeres</th>
                    <th>Nuevo Ingreso</th>
                    <th>Reingreso</th>
                    <th>Traslado</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($estadisticas_grado_turno['estadisticas'] as $estadistica)
                <tr>
                    <td>{{ $estadistica['grado'] }}</td>
                    <td>{{ $estadistica['turno'] }}</td>
                    <td>{{ $estadistica['varones'] }}</td>
                    <td>{{ $estadistica['mujeres'] }}</td>
                    <td>{{ $estadistica['nuevos_ingresos'] }}</td>
                    <td>{{ $estadistica['reingresos'] }}</td>
                    <td>{{ $estadistica['traslados'] }}</td>
                    <td><span class="numero-badge">{{ $estadistica['total'] }}</span></td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2"><strong>TOTAL</strong></td>
                    <td><strong>{{ $estadisticas_grado_turno['totales']['varones'] }}</strong></td>
                    <td><strong>{{ $estadisticas_grado_turno['totales']['mujeres'] }}</strong></td>
                    <td><strong>{{ $estadisticas_grado_turno['totales']['nuevos_ingresos'] }}</strong></td>
                    <td><strong>{{ $estadisticas_grado_turno['totales']['reingresos'] }}</strong></td>
                    <td><strong>{{ $estadisticas_grado_turno['totales']['traslados'] }}</strong></td>
                    <td><strong><span class="numero-badge">{{ $estadisticas_grado_turno['totales']['total'] }}</span></strong></td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    <!-- Estadísticas por Día -->
    @if(isset($estadisticas_por_dia) && ($tipo_reporte == 'por_dia' || $tipo_reporte == 'completo'))
    <div class="section {{ ($tipo_reporte == 'completo' && (count(data_get($estadisticas_grupo_turno, 'estadisticas', [])) > 0 || count(data_get($estadisticas_grado_turno, 'estadisticas', [])) > 0)) ? 'page-break' : '' }}">
        <div class="section-title">ESTADÍSTICAS POR DÍA</div>
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Grado</th>
                    <th>Turno</th>
                    <th>Masculino</th>
                    <th>Femenino</th>
                    <th>Nuevo Ingreso</th>
                    <th>Reingreso</th>
                    <th>Traslado</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($estadisticas_por_dia['estadisticas'] as $fechaData)
                <!-- Fila de encabezado de fecha con icono -->
                <tr class="fecha-header">
                    <td colspan="9" style="background-color: #f8f9fa; font-weight: bold; padding: 8px;">
                        📅 {{ \Carbon\Carbon::parse($fechaData['fecha'])->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                    </td>
                </tr>

                <!-- Fila de totales del día -->
                <tr class="total-dia-row" style="background-color: #e3f2fd;">
                    <td><strong>TOTAL DEL DÍA</strong></td>
                    <td>—</td>
                    <td></td>
                    <td><strong>{{ $fechaData['totales']['varones'] }}</strong></td>
                    <td><strong>{{ $fechaData['totales']['mujeres'] }}</strong></td>
                    <td><strong>{{ $fechaData['totales']['nuevos_ingresos'] }}</strong></td>
                    <td><strong>{{ $fechaData['totales']['reingresos'] }}</strong></td>
                    <td><strong>{{ $fechaData['totales']['traslados'] }}</strong></td>
                    <td><strong><span class="numero-badge">{{ $fechaData['totales']['total'] }}</span></strong></td>
                </tr>

                <!-- Filas de estadísticas por grado y turno -->
                @foreach($fechaData['estadisticas'] as $estadistica)
                <tr>
                    <td></td>
                    <td>{{ $estadistica['grado'] }}</td>
                    <td>{{ $estadistica['turno'] }}</td>
                    <td>{{ $estadistica['varones'] }}</td>
                    <td>{{ $estadistica['mujeres'] }}</td>
                    <td>{{ $estadistica['nuevos_ingresos'] }}</td>
                    <td>{{ $estadistica['reingresos'] }}</td>
                    <td>{{ $estadistica['traslados'] }}</td>
                    <td>{{ $estadistica['total'] }}</td>
                </tr>
                @endforeach
                @endforeach

                <!-- Fila de total general -->
                <tr class="total-row">
                    <td colspan="3"><strong>TOTAL GENERAL</strong></td>
                    <td><strong>{{ $estadisticas_por_dia['totales']['varones'] }}</strong></td>
                    <td><strong>{{ $estadisticas_por_dia['totales']['mujeres'] }}</strong></td>
                    <td><strong>{{ $estadisticas_por_dia['totales']['nuevos_ingresos'] }}</strong></td>
                    <td><strong>{{ $estadisticas_por_dia['totales']['reingresos'] }}</strong></td>
                    <td><strong>{{ $estadisticas_por_dia['totales']['traslados'] }}</strong></td>
                    <td><strong><span class="numero-badge">{{ $estadisticas_por_dia['totales']['total'] }}</span></strong></td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    <!-- Estadísticas por Usuario -->
    @if(isset($estadisticas_por_usuario) && ($tipo_reporte == 'por_usuario' || $tipo_reporte == 'completo'))
    <div class="section {{ ($tipo_reporte == 'completo' && (count(data_get($estadisticas_grupo_turno, 'estadisticas', [])) > 0 || count(data_get($estadisticas_grado_turno, 'estadisticas', [])) > 0 || count(data_get($estadisticas_por_dia, 'estadisticas', [])) > 0)) ? 'page-break' : '' }}">
        <div class="section-title">ESTADÍSTICAS POR USUARIO</div>
        <table>
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Varones</th>
                    <th>Mujeres</th>
                    <th>Nuevo Ingreso</th>
                    <th>Reingreso</th>
                    <th>Traslado</th>
                    <th>Total</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach($estadisticas_por_usuario['estadisticas'] as $estadistica)
                <tr>
                    <td>{{ $estadistica['usuario'] }}</td>
                    <td>{{ $estadistica['varones'] }}</td>
                    <td>{{ $estadistica['mujeres'] }}</td>
                    <td>{{ $estadistica['nuevos_ingresos'] }}</td>
                    <td>{{ $estadistica['reingresos'] }}</td>
                    <td>{{ $estadistica['traslados'] }}</td>
                    <td><span class="numero-badge">{{ $estadistica['total'] }}</span></td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td><strong>TOTAL</strong></td>
                    <td><strong>{{ $estadisticas_por_usuario['varones_general'] }}</strong></td>
                    <td><strong>{{ $estadisticas_por_usuario['mujeres_general'] }}</strong></td>
                    <td><strong>{{ $estadisticas_por_usuario['nuevos_ingresos_general'] }}</strong></td>
                    <td><strong>{{ $estadisticas_por_usuario['reingresos_general'] }}</strong></td>
                    <td><strong>{{ $estadisticas_por_usuario['traslados_general'] }}</strong></td>
                    <td><strong><span class="numero-badge">{{ $estadisticas_por_usuario['total_general'] }}</span></strong></td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

</body>

</html>