<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Notas</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .info-table {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 4px;
        }

        .grades-table {
            width: 100%;
            border-collapse: collapse;
        }

        .grades-table th,
        .grades-table td {
            border: 1px solid #333;
            padding: 5px;
            text-align: center;
        }

        .grades-table th {
            background-color: #f0f0f0;
        }

        .text-left {
            text-align: left !important;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
    @include('pdf.header', [
    'nombreInstitucion' => $nombreInstitucion,
    'titulo' => 'REPORTE DE NOTAS POR ASIGNATURA',
    'subtitulo1' => 'Periodo: ' . ($metadata['periodo'] ?? '')
    ])

    <table class="info-table">
        <tr>
            <td><strong>Materia:</strong> {{ $metadata['materia'] ?? '' }}</td>
            <td><strong>Corte:</strong> {{ $metadata['corte'] ?? '' }}</td>
            <td><strong>Fecha de Impresión:</strong> {{ date('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td><strong>Grupo:</strong> {{ $metadata['grupo'] ?? '' }}</td>
            <td><strong>Docente:</strong> {{ $metadata['docente'] ?? '' }}</td>
        </tr>
    </table>

    <table class="grades-table">
        <thead>
            <tr>
                <th width="30">No.</th>
                <th class="text-left" width="120">Estudiante</th>
                @foreach($tasks as $task)
                @if($metadata['es_iniciativa'] ?? false)
                <th style="min-width: 100px;">{{ $task['nombre'] }}</th>
                @else
                <th>{{ $task['nombre'] }}<br><small>({{ $task['puntaje_maximo'] }})</small></th>
                @endif
                @endforeach
                @if(!($metadata['es_iniciativa'] ?? false))
                <th width="40">Acum.</th>
                <th width="40">Exam.</th>
                <th>Nota<br>Final</th>
                <th>Escala</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($students as $index => $student)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td class="text-left" style="font-size: 8px;">{{ $student['nombre_completo'] }}</td>
                @foreach($tasks as $task)
                @php
                $grade = $student['grades'][$task['id']] ?? ['display' => '-', 'indicadores_check' => [], 'indicador_config' => null, 'evidence_name' => null];
                @endphp
                <td class="text-left" style="vertical-align: top; font-size: 8px;">
                    @if(($metadata['es_iniciativa'] ?? false) && (!empty($grade['indicador_config']) || !empty($grade['evidence_name'])))
                        <div style="margin-bottom: 3px;">
                            <strong>{{ $grade['evidence_name'] ?? $task['nombre'] }}</strong>
                            @if(($grade['indicador_config']['type'] ?? '') !== 'select')
                            <span style="float: right;">({{ $grade['display'] }})</span>
                            @endif
                        </div>
                        @php
                            $config = $grade['indicador_config'];
                            $checks = $grade['indicadores_check'] ?? [];
                            $criteria = [];
                            if (isset($config['criterios']) && is_array($config['criterios'])) $criteria = $config['criterios'];
                            elseif (isset($config['criterio'])) {
                                $criteria = is_array($config['criterio']) ? array_values($config['criterio']) : [$config['criterio']];
                            }
                        @endphp
                        @foreach($criteria as $i => $crit)
                            @php
                                $isSelect = ($config['type'] ?? '') === 'select';
                                $isChecked = $isSelect 
                                    ? ($checks['respuesta'] ?? '') === $crit
                                    : (!empty($checks[$crit]) || !empty($checks[$i]) || !empty($checks[$i+1]));
                            @endphp
                            <div style="margin-left: 5px; color: {{ $isChecked ? '#000' : '#888' }}">
                                {!! $isChecked ? '&#10004;' : '&#9711;' !!} {{ $crit }}
                            </div>
                        @endforeach
                    @else
                        {{ $grade['display'] }}
                    @endif
                </td>
                @endforeach
                @if(!($metadata['es_iniciativa'] ?? false))
                <td>{{ $student['acumulado'] }}</td>
                <td>{{ $student['examen'] }}</td>
                <td><strong>{{ $student['nota_final'] }}</strong></td>
                <td>{{ $student['escala'] }}</td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>


</body>

</html>