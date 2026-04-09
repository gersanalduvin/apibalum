<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Consolidado de Notas</title>
    <style>
        @page {
            margin: 5mm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 8pt;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .header {
            width: 100%;
            margin-bottom: 10px;
            text-align: center;
        }

        .logo {
            float: left;
            width: 60px;
        }

        .school-info h1 {
            font-size: 14pt;
            margin: 0;
            text-transform: uppercase;
        }

        .school-info h2 {
            font-size: 10pt;
            margin: 2px 0;
            color: #666;
        }

        .report-title {
            font-weight: bold;
            font-size: 11pt;
            margin-top: 5px;
            text-transform: uppercase;
        }

        .report-subtitle {
            font-size: 10pt;
            margin-bottom: 5px;
        }

        .clear {
            clear: both;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        thead {
            display: table-row-group;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 2px;
            text-align: center;
        }

        th {
            background-color: #fff;
            font-weight: bold;
            text-transform: uppercase;
        }

        .student-name {
            text-align: left;
            padding-left: 5px;
            width: 180px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .area-header {
            font-size: 7pt;
            height: 40px;
        }

        .materia-header {
            font-size: 7pt;
            width: 25px;
        }

        .nf-col {
            width: 35px;
            font-weight: bold;
        }

        .num-col {
            width: 20px;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .no-grade {
            color: #ccc;
        }

        .empty-row {
            height: 15px;
        }

        .teacher-info {
            font-weight: bold;
            margin-top: 5px;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="logo">
            @if(file_exists(public_path('logopp.png')))
            <img src="{{ public_path('logopp.png') }}" alt="Logo" style="width: 60px;">
            @else
            <div style="width: 60px; height: 60px; border: 1px dashed #ccc; margin: 0 auto; line-height: 60px; font-size: 10px; color: #999;">LOGO</div>
            @endif
        </div>
        <div class="school-info">
            <h1>{{ $nombreInstitucion }}</h1>
            <h2>{{ config('app.subtitulo_institucion', '') }}</h2>
            <div class="report-title">CONSOLIDADO DE NOTAS - {{ $corte_nombre }} {{ $periodo_lectivo->nombre }}</div>
            <div class="report-subtitle">Lista de Estudiantes de {{ $grupo->grado->nombre }} - {{ $grupo->seccion->nombre }} ({{ $grupo->turno->nombre }})</div>
            <div class="teacher-info">Profesor(a) de Grado: {{ $grupo->docenteGuia ? ($grupo->docenteGuia->primer_nombre . ' ' . $grupo->docenteGuia->primer_apellido) : 'N/A' }}</div>
        </div>
    </div>
    <div class="clear"></div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" class="num-col">#</th>
                <th rowspan="2" class="student-name">Estudiante</th>
                @foreach($asignaturasConAreas as $area)
                <th colspan="{{ count($area['asignaturas']) * ($mostrar_escala ? 2 : 1) }}" class="area-header">
                    {{ $area['area_nombre'] }}
                </th>
                @endforeach
                <th rowspan="2" class="nf-col">NF</th>
            </tr>
            <tr>
                @foreach($asignaturasConAreas as $area)
                @foreach($area['asignaturas'] as $asignatura)
                <th class="materia-header">
                    {{ $asignatura->materia->abreviatura ?: substr($asignatura->materia->nombre, 0, 4) }}
                </th>
                @if($mostrar_escala)
                <th class="materia-header" style="background-color: #f0f0f0; width: 20px;">Ec</th>
                @endif
                @endforeach
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($consolidadoData as $index => $data)
            <tr>
                <td class="num-col">{{ $index + 1 }}</td>
                <td class="student-name">{{ $data['estudiante']->nombre_completo }}</td>
                @foreach($allAsignaturas as $asignatura)
                <td>
                    @php
                    $nota = $data['notas'][$asignatura->id] ?? null;
                    @endphp
                    @if($nota !== null)
                    {{ number_format($nota, 0) }}
                    @else
                    <span class="no-grade">-</span>
                    @endif
                </td>
                @if($mostrar_escala)
                <td style="font-size: 6pt; background-color: #f9f9f9;">
                    @php
                    $cualitativo = '-';
                    if ($nota !== null) {
                    foreach ($asignatura->escala->detalles as $detalle) {
                    if ($nota >= $detalle->rango_inicio && $nota <= $detalle->rango_fin) {
                        $cualitativo = $detalle->abreviatura;
                        break;
                        }
                        }
                        }
                        @endphp
                        {{ $cualitativo }}
                </td>
                @endif
                @endforeach
                <td class="nf-col">
                    @if($data['nf'] !== null)
                    {{ number_format($data['nf'], 0) }}
                    @else
                    -
                    @endif
                </td>
            </tr>
            @endforeach
            {{-- Add empty rows if needed to fill page --}}
        </tbody>
    </table>
</body>

</html>