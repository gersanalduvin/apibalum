<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Boletín Cualitativo - {{ $grupo->grado->nombre }}</title>
    <style>
        @page {
            margin: 0.2cm;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 7.5pt;
            line-height: 1.0;
            color: #000;
        }

        .page-break {
            page-break-after: always;
            clear: both;
        }

        /* Landscape Split (Page 1) */
        .landscape-page {
            width: 100%;
            height: 160mm; /* Reduced to strictly fit inside the scaled PDF boundaries */
            position: relative;
            overflow: hidden;
            page-break-after: always;
        }

        .split-container {
            width: 100%;
            height: 100%;
            display: table;
            table-layout: fixed;
        }

        .panel {
            display: table-cell;
            vertical-align: top;
            padding: 10px 20px;
            width: 50%;
        }

        .panel-left {
            /* border-right: 1px dashed #ccc; */
        }

        .indicators-page {
            width: 100%;
        }

        .no-break {
            page-break-inside: avoid;
        }

        /* Cover Styles (Right Panel) */
        .cover-content {
            text-align: center;
        }

        .school-name {
            font-size: 14pt;
            font-weight: bold;
            font-style: italic;
            margin-top: 10px;
        }

        .school-info {
            font-size: 9pt;
            margin-bottom: 5px;
        }

        .motto {
            font-size: 9pt;
            font-weight: bold;
            font-style: italic;
            margin: 15px 0;
        }

        .logo-container {
            margin: 20px 0;
        }

        .evaluation-title {
            font-size: 13pt;
            font-weight: bold;
            font-style: italic;
            margin-top: 30px;
        }

        .student-data-table {
            width: 100%;
            margin-top: 30px;
            font-size: 10pt;
            text-align: left;
        }

        .student-data-table td {
            border: none;
            padding: 5px 0;
        }

        .line-under {
            border-bottom: 1px solid #000;
        }

        /* Back Page (Left Panel) */
        .parent-eval-item {
            margin-bottom: 15px;
        }

        .parent-eval-item ul {
            list-style: disc;
            margin-left: 20px;
            padding: 0;
        }

        .parent-eval-item li {
            margin-bottom: 3px;
        }

        .checkbox-row {
            margin-left: 25px;
            margin-top: 3px;
            font-size: 9pt;
        }

        .checkbox-box {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            vertical-align: middle;
            margin-right: 5px;
            margin-left: 10px;
        }

        .observation-area {
            margin-top: 20px;
        }

        .obs-line {
            border-bottom: 1px solid #000;
            height: 18px;
            margin-bottom: 2px;
        }

        /* Indicators Table (Page 2) */
        .indicators-header {
            text-align: center;
            margin-bottom: 10px;
        }

        .indicators-header h2 {
            font-size: 10pt;
            margin: 2px 0;
        }

        .main-table {
            width: 100%;
            border-collapse: collapse;
        }

        .main-table th,
        .main-table td {
            border: 1px solid #000;
            padding: 1px 2px;
            vertical-align: middle;
        }

        .main-table th {
            background-color: #fff;
            font-weight: bold;
        }

        .col-no {
            width: 15px;
            text-align: center;
        }

        .col-aa {
            width: 20px;
            text-align: center;
        }

        .col-ap {
            width: 20px;
            text-align: center;
        }

        .indicator-text {
            text-align: left;
            font-size: 7pt;
        }

        .inline-checkbox {
            display: inline-block;
            width: 10px;
            height: 10px;
            border: 1px solid #000;
            margin: 0 2px;
            vertical-align: middle;
        }

        .check-mark {
            font-family: DejaVu Sans, sans-serif;
            text-align: center;
            font-weight: bold;
        }

        .column-container {
            width: 100%;
            column-count: 2;
            column-gap: 5px;
        }

        .table-wrapper {
            break-inside: avoid;
        }
    </style>
</head>

<body>
    @foreach($estudiantes as $index => $estudianteData)
    <div class="student-report" style="{{ $index > 0 ? 'page-break-before: always;' : '' }}">
        {{-- Data Extraction and preparation --}}
        @php
        $coverEvidences = [];
        $academicEvidences = [];

        foreach($estudianteData['calificaciones'] as $calificacion) {
        foreach($calificacion['notas_por_corte'] as $corteId => $corteData) {
        // If filtering by corte, only use the selected one
        if ($corte_id_filtro && $corteId != $corte_id_filtro) continue;

        foreach($corteData['notas'] as $grade) {
        // Map structure to template needs
        $mappedGrade = [
        'evidence_name' => $grade['evidence_name'] ?? 'Evidencia',
        'indicador_config' => $grade['indicador_config'] ?? [],
        'indicadores_check' => $grade['indicadores_check'] ?? [],
        'display' => $grade['nota'] ?? ''
        ];

        if (($mappedGrade['indicador_config']['type'] ?? '') === 'select') {
        $coverEvidences[] = $mappedGrade;
        } else {
        $academicEvidences[] = $mappedGrade;
        }
        }
        }
        }
        @endphp

        {{-- PAGE 1: Covers --}}
        <div class="landscape-page">
            <div class="split-container">
                {{-- LEFT PANEL: Back Cover (Dynamic Radio Evidences) --}}
                <div class="panel panel-left">
                    <div class="parent-eval-section">
                        @foreach($coverEvidences as $grade)
                        <div class="parent-eval-item">
                            <ul>
                                <li>{{ $grade['evidence_name'] }}</li>
                            </ul>
                            <div class="checkbox-row">
                                @php
                                $config = $grade['indicador_config'];
                                $checks = $grade['indicadores_check'] ?? [];
                                $criteria = $config['criterio'] ?? (is_array($config['criterios']) ? $config['criterios'] : []);
                                if(!is_array($criteria)) $criteria = [$criteria];
                                $answer = $checks['respuesta'] ?? '';
                                @endphp

                                @foreach($criteria as $crit)
                                {{ $crit }}
                                <span class="checkbox-box">
                                    @if($answer == $crit)
                                    <span class="check-mark" style="font-size: 8pt; margin-top: -3px; display: block;">&#10004;</span>
                                    @endif
                                </span>
                                @endforeach
                            </div>
                        </div>
                        @endforeach

                        @if(empty($coverEvidences))
                        <div style="font-style: italic; color: #999; margin-bottom: 20px;">No se encontraron evaluaciones conductuales dinamicas.</div>
                        @endif

                        <div class="observation-area">
                            <strong>Observación.</strong>
                            <div style="border: 1px solid #ccc; min-height: 120px; padding: 6px; margin-top: 5px; font-size: 8.5pt; line-height: 1.4; overflow: hidden;">
                                {{ $estudianteData['observacion'] ?? '' }}
                            </div>
                        </div>

                        <div style="margin-top: 15px; text-align: left; font-size: 8.5pt;">
                            Firma del Padre y/o Responsable
                        </div>
                    </div>
                </div>

                {{-- RIGHT PANEL: Front Cover --}}
                <div class="panel">
                    <div class="cover-content">
                        <div class="school-name">{{ config('institucion.cualitativo.nombre') }}</div>
                         <div class="logo-container">
                            @php $logoPath = public_path(config('institucion.cualitativo.logo')); @endphp
                            @if(file_exists($logoPath))
                            <img src="{{ $logoPath }}" alt="Logo" style="width: 150px;">
                            @else
                            <div style="width: 180px; height: 180px; border: 1px dashed #ccc; margin: 0 auto; line-height: 180px;">LOGO</div>
                            @endif
                        </div>


                        <div class="evaluation-title">
                            @php
                            $corteNombre = 'Evaluación';
                            if ($corte_id_filtro) {
                            $corteModel = \App\Models\ConfigNotSemestreParcial::find($corte_id_filtro);
                            $corteNombre = $corteModel ? $corteModel->nombre : 'Evaluación';

                            // Transformar nombres estándar a los solicitados
                            $corteNombre = str_ireplace(
                            ['Corte 1', 'Corte 2', 'Corte 3', 'Corte 4'],
                            ['Primera Evaluación', 'Segunda Evaluación', 'Tercera Evaluación', 'Cuarta Evaluación'],
                            $corteNombre
                            );
                            }
                            @endphp
                            {{ $corteNombre }} {{ $periodo_lectivo->nombre ?? date('Y') }}
                        </div>

                        <table class="student-data-table">
                            <tr>
                                <td style="width: 70%;">{{ $grupo->grado->nombre }} {{ $grupo->seccion->nombre }} - {{ $grupo->turno->nombre ?? '' }}</td>
                                <td>Fecha: <span class="line-under" style="display: inline-block; width: 80px; text-align: center;">{{ date('d-m-Y') }}</span></td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding-top: 15px;"><strong style="font-weight: bold;">Nombre del Niño(a):</strong> <span class="line-under" style="display: inline-block; width: 400px; text-align: left; padding-left: 10px;">{{ $estudianteData['estudiante']->nombre_completo }}</span></td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding-top: 15px;"><strong style="font-weight: bold;">Profesora:</strong> <span class="line-under" style="display: inline-block; width: 400px; text-align: left; padding-left: 10px;">{{ $grupo->docenteGuia->nombre_completo ?? 'No asignado' }}</span></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="indicators-page">
            {{-- PAGE 2: Indicators --}}
            <div class="indicators-header" style="text-align: center; margin-bottom: 10px; padding-top: 5px; width: 50%; padding-right: 7.5px; box-sizing: border-box;">
                <div style="font-weight: bold; font-size: 9pt;">Ámbitos de Aprendizaje:</div>
                <div style="font-weight: bold; font-style: italic; font-size: 8.5pt;">Dimensiones: Comunicación y Lenguaje, Cognitiva, Física, Emocional y Social.</div>
            </div>
            <div style="width: 100%; display: table; table-layout: fixed; border-spacing: 15px 0;">
                @php
                // 1. Calculate weighted height for each evidence to balance columns
                $weightedEvidences = [];
                $totalWeight = 0;
                foreach($academicEvidences as $index => $grade) {
                $config = $grade['indicador_config'] ?? [];
                $criteria = $config['criterios'] ?? (isset($config['criterio']) ? (is_array($config['criterio']) ? $config['criterio'] : [$config['criterio']]) : []);

                // Weight: 1 (title) + ~0.33 per criterion (assuming 3 criteria per line)
                $weight = 1 + (ceil(count($criteria) / 3) * 0.8);
                $weightedEvidences[] = [
                'data' => $grade,
                'weight' => $weight,
                'original_index' => $index
                ];
                $totalWeight += $weight;
                }

                // 2. Find split point (closest to 50% of total weight)
                $leftChunk = [];
                $rightChunk = [];
                $currentWeight = 0;
                $splitIndexFound = false;

                foreach($weightedEvidences as $item) {
                if (!$splitIndexFound && ($currentWeight + $item['weight'] / 2) <= ($totalWeight / 2)) {
                    $leftChunk[]=$item;
                    $currentWeight +=$item['weight'];
                    } else {
                    $splitIndexFound=true;
                    $rightChunk[]=$item;
                    }
                    }

                    $chunks=[$leftChunk, $rightChunk];
                    @endphp

                    @foreach($chunks as $chunkIndex=> $chunk)
                    <div style="display: table-cell; vertical-align: top; width: 50%;">
                        <table class="main-table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th class="col-no">No.</th>
                                    <th>Evidencias de Aprendizaje</th>
                                    <th class="col-aa">AA</th>
                                    <th class="col-ap">AP</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($chunk as $item)
                                @php
                                $grade = $item['data'];
                                $config = $grade['indicador_config'] ?? [];
                                $checks = $grade['indicadores_check'] ?? [];
                                $criteria = $config['criterios'] ?? (isset($config['criterio']) ? (is_array($config['criterio']) ? $config['criterio'] : [$config['criterio']]) : []);

                                $isSelect = ($config['type'] ?? '') === 'select';
                                $finalScale = $grade['display'] ?? ($grade['escala_abreviatura'] ?? '');
                                @endphp
                                <tr>
                                    <td class="col-no" style="font-weight: normal; font-size: 7.5pt;">{{ $item['original_index'] + 1 }}</td>
                                    <td class="indicator-text" style="font-weight: normal; font-size: 8pt; line-height: 1.2;">
                                        @php
                                        $evName = $grade['evidence_name'] ?? 'Evidencia';
                                        $evParts = explode('|', $evName);
                                        @endphp
                                        {{ $evParts[0] }}
                                        @foreach($criteria as $i => $crit)
                                        @php
                                        $isChecked = !empty($checks[$crit]) || !empty($checks[$i]) || !empty($checks[$i+1]);
                                        @endphp
                                        <span style="white-space: nowrap;">{{ $crit }} <span class="inline-checkbox">@if($isChecked)<span class="check-mark" style="font-size: 7pt; margin-top: -3px; display: block;">&#10004;</span>@endif</span></span>
                                        @endforeach
                                        {{ $evParts[1] ?? '' }}
                                    </td>
                                    <td class="col-aa check-mark">@if($finalScale == 'AA') &#10004; @endif</td>
                                    <td class="col-ap check-mark">@if($finalScale == 'AP') &#10004; @endif</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endforeach
            </div> {{-- Close table-container --}}
        </div> {{-- Close indicators-page --}}
    </div> {{-- Close student-report --}}
    @endforeach
</body>

</html>