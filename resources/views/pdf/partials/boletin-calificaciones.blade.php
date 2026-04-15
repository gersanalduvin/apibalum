<div style="padding: 10px;">
    {{-- Header with logo and school name side-by-side --}}
    <table style="width: 100%; border: none; margin-bottom: 15px;">
        <tr style="border: none;">
            <td style="width: 15%; border: none; vertical-align: middle; text-align: left;">
                @php $logoPath = public_path(config('institucion.cuantitativo.logo')); @endphp
                @if(file_exists($logoPath))
                <img src="{{ $logoPath }}" alt="Logo" style="width: 75px; background-color: white;">
                @else
                <div style="width: 75px; height: 75px; border: 1px dashed #ccc; line-height: 75px; font-size: 8px; color: #999; text-align: center;">LOGO</div>
                @endif
            </td>
            <td style="width: 70%; border: none; vertical-align: middle; text-align: center;">
                <div style="font-size: 18pt; font-weight: bold; margin-bottom: 5px; color: #000;">{{ config('institucion.cuantitativo.nombre') }}</div>
                <div style="font-size: 11pt; font-style: italic; margin-bottom: 8px; color: #333;">{{ config('institucion.cuantitativo.motto') }}</div>
                <div style="font-size: 15pt; font-weight: bold; letter-spacing: 2px; white-space: nowrap;">BOLETÍN ESCOLAR {{ $periodo_lectivo->nombre ?? date('Y') }}</div>
            </td>
            <td style="width: 15%; border: none;"></td>
        </tr>
    </table>

    {{-- Student info in single row --}}
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 8pt;">
        <tr>
            <td style="text-align: left; padding: 5px; border: 1px solid #000; width: 65%;">
                <strong>Nombre del alumno (a):</strong> {{ mb_strtoupper($estudiante->nombre_completo, 'UTF-8') }}
            </td>
            <td style="text-align: center; padding: 5px; border: 1px solid #000; width: 35%;">
                <strong>{{ mb_strtoupper($grupo->grado->nombre ?? '', 'UTF-8') }} - {{ mb_strtoupper($grupo->seccion->nombre ?? '', 'UTF-8') }}</strong>
            </td>
        </tr>
    </table>
    {{-- Visibility Logic & Helpers --}}
    @php
    $orden = $corte_orden_filtro ?? 4;
    $showC1 = true; // Always show first cut
    $showC2 = $orden >= 2;
    $showIS = $orden >= 2;
    $showC3 = $orden >= 3;
    $showC4 = $orden >= 4;
    $showIIS = $orden >= 4;
    $showFinal = ($orden >= 4 || $corte_orden_filtro === null);

    // Calculate colspans
    $s1_colspan = 2;
    if ($showC2) $s1_colspan += 2;
    if ($showIS) $s1_colspan += 2;

    $s2_colspan = 0;
    if ($showC3) $s2_colspan += 2;
    if ($showC4) $s2_colspan += 2;
    if ($showIIS) $s2_colspan += 2;

    $final_colspan = $showFinal ? 2 : 0;
    $total_cols = 1 + $s1_colspan + $s2_colspan + $final_colspan;

    // Formatting Helpers
    $isFilterActive = !is_null($corte_id_filtro);

    $formatNota = function($val) use ($isFilterActive) {
    if ($val === null || $val === '' || $val === 0 || $val === '0') {
    return $isFilterActive ? '0' : ' ';
    }
    return $val;
    };
    $formatCual = function($val) use ($isFilterActive) {
    if ($val === null || $val === '-' || $val === '' || $val === ' ') {
    return $isFilterActive ? '-' : ' ';
    }
    return $val;
    };

    $currentArea = null;
    $totalPorCorte = [1 => [], 2 => [], 3 => [], 4 => []];
    $totalFinales = [];
    $totalS1 = [];
    $totalS2 = [];
    @endphp

    {{-- Grades table --}}
    <table class="main-table">
        <thead>
            <tr>
                <th rowspan="3" style="width: 30%; vertical-align: middle;">ASIGNATURAS</th>
                <th colspan="{{ $s1_colspan }}" style="font-size: 7.5pt;">PRIMER SEMESTRE</th>
                @if($s2_colspan > 0)
                <th colspan="{{ $s2_colspan }}" style="font-size: 7.5pt;">SEGUNDO SEMESTRE</th>
                @endif
                @if($showFinal)
                <th rowspan="2" colspan="2" style="width: 10%; vertical-align: middle; font-size: 7.5pt;">NOTA<br>FINAL</th>
                @endif
            </tr>
            <tr>
                <th colspan="2">I CORTE</th>
                @if($showC2) <th colspan="2">II CORTE</th> @endif
                @if($showIS) <th colspan="2" style="background-color: #f0f0f0;">IS</th> @endif

                @if($showC3) <th colspan="2">III CORTE</th> @endif
                @if($showC4) <th colspan="2">IV CORTE</th> @endif
                @if($showIIS) <th colspan="2" style="background-color: #f0f0f0;">IIS</th> @endif
            </tr>
            <tr style="font-size: 6.5pt; font-weight: bold;">
                <th style="width: 5%;">CUANT</th>
                <th style="width: 5%;">CUALI</th>
                @if($showC2)
                <th style="width: 5%;">CUANT</th>
                <th style="width: 5%;">CUALI</th>
                @endif
                @if($showIS)
                <th style="width: 5%; background-color: #f0f0f0;">CUANT</th>
                <th style="width: 5%; background-color: #f0f0f0;">CUALI</th>
                @endif
                @if($showC3)
                <th style="width: 5%;">CUANT</th>
                <th style="width: 5%;">CUALI</th>
                @endif
                @if($showC4)
                <th style="width: 5%;">CUANT</th>
                <th style="width: 5%;">CUALI</th>
                @endif
                @if($showIIS)
                <th style="width: 5%; background-color: #f0f0f0;">CUANT</th>
                <th style="width: 5%; background-color: #f0f0f0;">CUALI</th>
                @endif
                @if($showFinal)
                <th style="width: 5%;">CUANT</th>
                <th style="width: 5%;">CUALI</th>
                @endif
            </tr>
        </thead>

        <tbody>
            @foreach($calificaciones as $calificacion)
            {{-- Show area header if it's a new area --}}
            @if($currentArea !== $calificacion['area_id'])
            @php $currentArea = $calificacion['area_id']; @endphp
            <tr class="area-row">
                <td colspan="{{ $total_cols }}" style="text-align: left; padding-left: 10px; font-weight: bold; font-size: 7.5pt; background-color: #e8e8e8;">{{ mb_strtoupper($calificacion['area_nombre'], 'UTF-8') }}</td>
            </tr>
            @endif

            @php
                $inclProm = ($calificacion['incluir_en_promedio'] ?? false);
                $inclBol  = ($calificacion['incluir_en_boletin'] ?? true);
            @endphp

            {{-- Subject row --}}
            <tr class="asignatura-row">
                <td style="text-align: left; padding-left: 5px; font-size: 7.5pt;">{{ $calificacion['asignatura_nombre'] }}</td>

                {{-- Corte 1 --}}
                @php
                $corte1 = collect($calificacion['notas_por_corte'])->firstWhere('corte_orden', 1);
                $c1 = $corte1['promedio'] ?? null;
                $c1Cual = $corte1['promedio_cualitativo'] ?? '-';
                if ($c1 !== null && $inclProm) { $totalPorCorte[1][] = (float)$c1; }
                @endphp
                <td>{{ $formatNota($c1) }}</td>
                <td>{{ $formatCual($c1Cual) }}</td>

                @if($showC2)
                {{-- Corte 2 --}}
                @php
                $corte2 = collect($calificacion['notas_por_corte'])->firstWhere('corte_orden', 2);
                $c2 = $corte2['promedio'] ?? null;
                $c2Cual = $corte2['promedio_cualitativo'] ?? '-';
                if ($c2 !== null && $inclProm) { $totalPorCorte[2][] = (float)$c2; }
                @endphp
                <td>{{ $formatNota($c2) }}</td>
                <td>{{ $formatCual($c2Cual) }}</td>
                @else
                @php $c2 = null; @endphp
                @endif

                @if($showIS)
                {{-- IS (Semester 1 Average) --}}
                @php
                $isProm = null;
                if ($c1 !== null || $c2 !== null) {
                $vals = array_filter([(float)$c1, (float)$c2], fn($v) => !is_null($v) && $v > 0);
                $isProm = count($vals) > 0 ? round(array_sum($vals) / count($vals)) : null;
                }
                $isCual = '-';
                if($isProm >= 90) $isCual = 'AA';
                elseif($isProm >= 76) $isCual = 'AS';
                elseif($isProm >= 60) $isCual = 'AF';
                elseif($isProm !== null) $isCual = 'AI';

                if ($isProm !== null && $inclProm) { $totalS1[] = (float)$isProm; }
                @endphp
                <td style="background-color: #f0f0f0; font-weight: bold;">{{ $formatNota($isProm) }}</td>
                <td style="background-color: #f0f0f0; font-weight: bold;">{{ $formatCual($isCual) }}</td>
                @endif

                @if($showC3)
                {{-- Corte 3 --}}
                @php
                $corte3 = collect($calificacion['notas_por_corte'])->firstWhere('corte_orden', 3);
                $c3 = $corte3['promedio'] ?? null;
                $c3Cual = $corte3['promedio_cualitativo'] ?? '-';
                if ($c3 !== null && $inclProm) { $totalPorCorte[3][] = (float)$c3; }
                @endphp
                <td>{{ $formatNota($c3) }}</td>
                <td>{{ $formatCual($c3Cual) }}</td>
                @else
                @php $c3 = null; @endphp
                @endif

                @if($showC4)
                {{-- Corte 4 --}}
                @php
                $corte4 = collect($calificacion['notas_por_corte'])->firstWhere('corte_orden', 4);
                $c4 = $corte4['promedio'] ?? null;
                $c4Cual = $corte4['promedio_cualitativo'] ?? '-';
                if ($c4 !== null && $inclProm) { $totalPorCorte[4][] = (float)$c4; }
                @endphp
                <td>{{ $formatNota($c4) }}</td>
                <td>{{ $formatCual($c4Cual) }}</td>
                @else
                @php $c4 = null; @endphp
                @endif

                @if($showIIS)
                {{-- IIS (Semester 2 Average) --}}
                @php
                $iisProm = null;
                if ($c3 !== null || $c4 !== null) {
                $vals = array_filter([(float)$c3, (float)$c4], fn($v) => !is_null($v) && $v > 0);
                $iisProm = count($vals) > 0 ? round(array_sum($vals) / count($vals)) : null;
                }
                $iisCual = '-';
                if($iisProm >= 90) $iisCual = 'AA';
                elseif($iisProm >= 76) $iisCual = 'AS';
                elseif($iisProm >= 60) $iisCual = 'AF';
                elseif($iisProm !== null) $iisCual = 'AI';

                if ($iisProm !== null && $inclProm) { $totalS2[] = (float)$iisProm; }
                @endphp
                <td style="background-color: #f0f0f0; font-weight: bold;">{{ $formatNota($iisProm) }}</td>
                <td style="background-color: #f0f0f0; font-weight: bold;">{{ $formatCual($iisCual) }}</td>
                @endif

                @if($showFinal)
                {{-- Final qualitative and quantitative --}}
                @php
                $notaFinal = $calificacion['promedios']['nota_final'] ?? null;
                $finalCual = $calificacion['promedios']['nota_final_cualitativo'] ?? '-';
                if ($notaFinal !== null && $inclProm) { $totalFinales[] = (float)$notaFinal; }
                @endphp
                <td style="font-weight: bold;">{{ $formatNota($notaFinal) }}</td>
                <td style="font-weight: bold;">{{ $formatCual($finalCual) }}</td>
                @endif
            </tr>
            @endforeach

            {{-- PROMEDIO ROW --}}
            @php
            $promedioLabel = function($arr) {
            if (count($arr) === 0) return null;
            $val = array_sum($arr) / count($arr);
            return number_format($val, 2, '.', '');
            };
            $promCualitativo = function($arr) {
            if (count($arr) === 0) return null;
            $avg = array_sum($arr) / count($arr);
            if ($avg >= 90) return 'AA';
            if ($avg >= 76) return 'AS';
            if ($avg >= 60) return 'AF';
            return 'AI';
            };
            @endphp
            <tr style="font-weight: bold; background-color: #f5f5f5;">
                <td style="text-align: center; font-weight: bold; vertical-align: middle;">PROMEDIO</td>
                <td style="vertical-align: middle;">{{ $formatNota($promedioLabel($totalPorCorte[1])) }}</td>
                <td style="vertical-align: middle;">{{ $formatCual($promCualitativo($totalPorCorte[1])) }}</td>

                @if($showC2)
                <td style="vertical-align: middle;">{{ $formatNota($promedioLabel($totalPorCorte[2])) }}</td>
                <td style="vertical-align: middle;">{{ $formatCual($promCualitativo($totalPorCorte[2])) }}</td>
                @endif

                @if($showIS)
                <td style="background-color: #f0f0f0; vertical-align: middle;">{{ $formatNota($promedioLabel($totalS1)) }}</td>
                <td style="background-color: #f0f0f0; vertical-align: middle;">{{ $formatCual($promCualitativo($totalS1)) }}</td>
                @endif

                @if($showC3)
                <td style="vertical-align: middle;">{{ $formatNota($promedioLabel($totalPorCorte[3])) }}</td>
                <td style="vertical-align: middle;">{{ $formatCual($promCualitativo($totalPorCorte[3])) }}</td>
                @endif

                @if($showC4)
                <td style="vertical-align: middle;">{{ $formatNota($promedioLabel($totalPorCorte[4])) }}</td>
                <td style="vertical-align: middle;">{{ $formatCual($promCualitativo($totalPorCorte[4])) }}</td>
                @endif

                @if($showIIS)
                <td style="background-color: #f0f0f0; vertical-align: middle;">{{ $formatNota($promedioLabel($totalS2)) }}</td>
                <td style="background-color: #f0f0f0; vertical-align: middle;">{{ $formatCual($promCualitativo($totalS2)) }}</td>
                @endif

                @if($showFinal)
                <td style="vertical-align: middle;">{{ $formatNota($promedioLabel($totalFinales)) }}</td>
                <td style="vertical-align: middle;">{{ $formatCual($promCualitativo($totalFinales)) }}</td>
                @endif
            </tr>
        </tbody>
    </table>
</div>