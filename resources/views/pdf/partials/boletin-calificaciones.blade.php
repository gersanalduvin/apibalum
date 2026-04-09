<div style="padding: 5px;">
    {{-- Header with logo and school name --}}
    <table style="width: 100%; border: none; margin-bottom: 10px;">
        <tr style="border: none;">
            <td style="width: 80px; border: none; vertical-align: middle; text-align: center;">
                @if(file_exists(public_path('logopp.png')))
                <img src="{{ public_path('logopp.png') }}" alt="Logo" style="width: 65px; background-color: white;">
                @else
                <div style="width: 65px; height: 65px; border: 1px dashed #ccc; margin: 0 auto; line-height: 65px; font-size: 8px; color: #999;">LOGO</div>
                @endif
            </td>
            <td style="border: none; vertical-align: middle; text-align: center;">
                <div style="font-size: 13px; font-weight: bold; font-style: italic;">Centro Escolar "Mis Primeros Pasos"</div>
                <div style="font-size: 12px; font-weight: bold; font-style: italic; text-decoration: underline;">Certificado de Calificaciones.</div>
            </td>
        </tr>
    </table>

    {{-- Student info --}}
    <table style="width: 100%; border: none; margin-bottom: 8px; font-size: 10px;">
        <tr style="border: none;">
            <td style="border: none; text-align: left; padding: 2px 0;">
                <strong>Nombres y Apellidos:</strong> {{ $estudiante->primer_nombre }} {{ $estudiante->segundo_nombre ?? '' }} {{ $estudiante->primer_apellido }} {{ $estudiante->segundo_apellido ?? '' }}
            </td>
        </tr>
        <tr style="border: none;">
            <td style="border: none; text-align: left; padding: 2px 0;">
                <strong>Código Estudiantil</strong> {{ !empty($estudiante->codigo_unico) ? $estudiante->codigo_unico : '___________________' }}
            </td>
        </tr>
        <tr style="border: none;">
            <td style="border: none; text-align: left; padding: 2px 0;">
                <strong>Grado y Sección:</strong> {{ $grupo->grado->nombre ?? '' }} {{ $grupo->seccion->nombre ?? '' }}
            </td>
        </tr>
    </table>

    {{-- Grades table --}}
    <table>
        <thead>
            <tr>
                <th rowspan="3" style="width: 28%; vertical-align: middle;">Materias</th>
                <th colspan="2">I PARCIAL</th>
                <th colspan="2">II PARCIAL</th>
                <th colspan="2">III PARCIAL</th>
                <th colspan="2">IV PARCIAL</th>
                <th rowspan="3" style="width: 5%; vertical-align: middle; font-size: 8px;">CUAL.</th>
                <th rowspan="3" style="width: 5%; vertical-align: middle; font-size: 8px;">Nota<br>Final</th>
            </tr>
            <tr>
                <th style="font-size: 7px;">CUAL.</th>
                <th style="font-size: 7px;">CUAN.</th>
                <th style="font-size: 7px;">CUAL.</th>
                <th style="font-size: 7px;">CUAN.</th>
                <th style="font-size: 7px;">CUAL.</th>
                <th style="font-size: 7px;">CUAN.</th>
                <th style="font-size: 7px;">CUAL.</th>
                <th style="font-size: 7px;">CUAN.</th>
            </tr>
        </thead>
        <tbody>
            @php
            $currentArea = null;
            $totalPorCorte = [1 => [], 2 => [], 3 => [], 4 => []];
            $totalFinales = [];
            @endphp

            @foreach($calificaciones as $calificacion)
            {{-- Show area header if it's a new area --}}
            @if($currentArea !== $calificacion['area_id'])
            @php $currentArea = $calificacion['area_id']; @endphp
            <tr class="area-row">
                <td colspan="11" style="text-align: center; font-weight: bold; font-size: 9px; background-color: #e8e8e8;">{{ strtoupper($calificacion['area_nombre']) }}</td>
            </tr>
            @endif

            {{-- Subject row --}}
            <tr class="asignatura-row">
                <td style="text-align: left; padding-left: 5px; font-size: 9px;">{{ $calificacion['asignatura_nombre'] }}</td>

                {{-- Corte 1 --}}
                @php
                $corte1 = collect($calificacion['notas_por_corte'])->firstWhere('corte_orden', 1);
                $showC1 = !$corte_id_filtro || ($corte_orden_filtro >= 1);
                $c1Nota = $showC1 ? ($corte1['promedio'] ?? null) : null;
                if ($c1Nota !== null) { $totalPorCorte[1][] = (float)$c1Nota; }
                @endphp
                <td>{{ $showC1 ? ($corte1['promedio_cualitativo'] ?? '-') : '-' }}</td>
                <td>{{ $showC1 ? ($corte1['promedio'] ?? '-') : '-' }}</td>

                {{-- Corte 2 --}}
                @php
                $corte2 = collect($calificacion['notas_por_corte'])->firstWhere('corte_orden', 2);
                $showC2 = !$corte_id_filtro || ($corte_orden_filtro >= 2);
                $c2Nota = $showC2 ? ($corte2['promedio'] ?? null) : null;
                if ($c2Nota !== null) { $totalPorCorte[2][] = (float)$c2Nota; }
                @endphp
                <td>{{ $showC2 ? ($corte2['promedio_cualitativo'] ?? '-') : '-' }}</td>
                <td>{{ $showC2 ? ($corte2['promedio'] ?? '-') : '-' }}</td>

                {{-- Corte 3 --}}
                @php
                $corte3 = collect($calificacion['notas_por_corte'])->firstWhere('corte_orden', 3);
                $showC3 = !$corte_id_filtro || ($corte_orden_filtro >= 3);
                $c3Nota = $showC3 ? ($corte3['promedio'] ?? null) : null;
                if ($c3Nota !== null) { $totalPorCorte[3][] = (float)$c3Nota; }
                @endphp
                <td>{{ $showC3 ? ($corte3['promedio_cualitativo'] ?? '-') : '-' }}</td>
                <td>{{ $showC3 ? ($corte3['promedio'] ?? '-') : '-' }}</td>

                {{-- Corte 4 --}}
                @php
                $corte4 = collect($calificacion['notas_por_corte'])->firstWhere('corte_orden', 4);
                $showC4 = !$corte_id_filtro || ($corte_orden_filtro >= 4);
                $c4Nota = $showC4 ? ($corte4['promedio'] ?? null) : null;
                if ($c4Nota !== null) { $totalPorCorte[4][] = (float)$c4Nota; }
                @endphp
                <td>{{ $showC4 ? ($corte4['promedio_cualitativo'] ?? '-') : '-' }}</td>
                <td>{{ $showC4 ? ($corte4['promedio'] ?? '-') : '-' }}</td>

                {{-- Final qualitative and quantitative --}}
                @php
                $notaFinal = (!$corte_id_filtro || $corte_orden_filtro >= 4) ? ($calificacion['promedios']['nota_final'] ?? null) : null;
                if ($notaFinal !== null) { $totalFinales[] = (float)$notaFinal; }
                @endphp
                <td>{{ (!$corte_id_filtro || $corte_orden_filtro >= 4) ? ($calificacion['promedios']['nota_final_cualitativo'] ?? '-') : '-' }}</td>
                <td>{{ $notaFinal ?? '-' }}</td>
            </tr>
            @endforeach

            {{-- PROMEDIO ROW --}}
            @php
            $promedioLabel = function($arr) {
            if (count($arr) === 0) return '-';
            return round(array_sum($arr) / count($arr));
            };
            // Simple cualitativo for promedio (AA >= 90, AS >= 76, AF >= 60, AI < 60)
                $promCualitativo=function($arr) {
                if (count($arr)===0) return '-' ;
                $avg=array_sum($arr) / count($arr);
                if ($avg>= 90) return 'AA';
                if ($avg >= 76) return 'AS';
                if ($avg >= 60) return 'AF';
                return 'AI';
                };
                @endphp
                <tr style="font-weight: bold; background-color: #f5f5f5;">
                    <td style="text-align: center; font-weight: bold;">Promedio</td>
                    <td>{{ $promCualitativo($totalPorCorte[1]) }}</td>
                    <td>{{ $promedioLabel($totalPorCorte[1]) }}</td>
                    <td>{{ $promCualitativo($totalPorCorte[2]) }}</td>
                    <td>{{ $promedioLabel($totalPorCorte[2]) }}</td>
                    <td>{{ $promCualitativo($totalPorCorte[3]) }}</td>
                    <td>{{ $promedioLabel($totalPorCorte[3]) }}</td>
                    <td>{{ $promCualitativo($totalPorCorte[4]) }}</td>
                    <td>{{ $promedioLabel($totalPorCorte[4]) }}</td>
                    <td>{{ $promCualitativo($totalFinales) }}</td>
                    <td>{{ $promedioLabel($totalFinales) }}</td>
                </tr>

                {{-- OTROS ROW --}}
                <tr style="font-weight: bold; background-color: #f5f5f5;">
                    <td style="text-align: center; font-weight: bold;">Otros</td>
                    <td colspan="10"></td>
                </tr>
                @php
                $getAbsCual = function($count) { return $count > 0 ? 'AI' : '-'; };
                $getAbsCuan = function($count) { return ''; }; // Empty in image
                $inasistencias = $estudianteData['inasistencias'] ?? [
                1 => ['justificadas' => 0, 'injustificadas' => 0],
                2 => ['justificadas' => 0, 'injustificadas' => 0],
                3 => ['justificadas' => 0, 'injustificadas' => 0],
                4 => ['justificadas' => 0, 'injustificadas' => 0],
                ];
                @endphp
                <tr>
                    <td style="text-align: left; padding-left: 5px; font-size: 9px;">Ausencias Justificadas</td>
                    <td>{{ $getAbsCual($inasistencias[1]['justificadas']) }}</td>
                    <td>{{ $getAbsCuan($inasistencias[1]['justificadas']) }}</td>
                    <td>{{ $getAbsCual($inasistencias[2]['justificadas']) }}</td>
                    <td>{{ $getAbsCuan($inasistencias[2]['justificadas']) }}</td>
                    <td>{{ $getAbsCual($inasistencias[3]['justificadas']) }}</td>
                    <td>{{ $getAbsCuan($inasistencias[3]['justificadas']) }}</td>
                    <td>{{ $getAbsCual($inasistencias[4]['justificadas']) }}</td>
                    <td>{{ $getAbsCuan($inasistencias[4]['justificadas']) }}</td>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td style="text-align: left; padding-left: 5px; font-size: 9px;">Ausencias Injustificadas</td>
                    <td>{{ $getAbsCual($inasistencias[1]['injustificadas']) }}</td>
                    <td>{{ $getAbsCuan($inasistencias[1]['injustificadas']) }}</td>
                    <td>{{ $getAbsCual($inasistencias[2]['injustificadas']) }}</td>
                    <td>{{ $getAbsCuan($inasistencias[2]['injustificadas']) }}</td>
                    <td>{{ $getAbsCual($inasistencias[3]['injustificadas']) }}</td>
                    <td>{{ $getAbsCuan($inasistencias[3]['injustificadas']) }}</td>
                    <td>{{ $getAbsCual($inasistencias[4]['injustificadas']) }}</td>
                    <td>{{ $getAbsCuan($inasistencias[4]['injustificadas']) }}</td>
                    <td colspan="2"></td>
                </tr>
        </tbody>
    </table>
</div>