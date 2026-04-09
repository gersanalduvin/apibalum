<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListasGrupoRequest;
use App\Services\ListasGrupoService;
use Illuminate\Support\Facades\DB;
use Barryvdh\Snappy\Facades\SnappyPdf;

class ListasGrupoController extends Controller
{
    public function __construct(private ListasGrupoService $service) {}

    public function catalogos(ListasGrupoRequest $request): \Illuminate\Http\JsonResponse
    {
        $p = $request->input('periodo_lectivo_id');
        $t = $request->input('turno_id');
        $data = $this->service->catalogos($p ? (int)$p : null, $t ? (int)$t : null);
        return $this->successResponse($data, 'Catálogos obtenidos exitosamente');
    }

    public function alumnos(ListasGrupoRequest $request): \Illuminate\Http\JsonResponse
    {
        $p = $request->input('periodo_lectivo_id');
        $g = $request->input('grupo_id');
        $t = $request->input('turno_id');
        $alumnos = $this->service->listarAlumnos($p ? (int)$p : null, $g ? (int)$g : null, $t ? (int)$t : null);
        return $this->successResponse($alumnos, 'Alumnos obtenidos exitosamente');
    }

    public function alumnosPdf(ListasGrupoRequest $request)
    {
        $periodoId = $request->input('periodo_lectivo_id');
        $grupoId = $request->input('grupo_id');
        $turnoId = $request->input('turno_id');

        // 1. Obtener Grupos (Uno o Todos)
        $query = DB::table('config_grupos')
            ->join('config_grado', 'config_grupos.grado_id', '=', 'config_grado.id')
            ->join('config_seccion', 'config_grupos.seccion_id', '=', 'config_seccion.id')
            ->join('config_turnos', 'config_grupos.turno_id', '=', 'config_turnos.id')
            ->leftJoin('users as guia', 'config_grupos.docente_guia', '=', 'guia.id')
            ->select(
                'config_grupos.id',
                'config_grado.nombre as grado',
                'config_grado.formato',
                'config_seccion.nombre as seccion',
                'config_turnos.nombre as turno',
                DB::raw("CONCAT(COALESCE(guia.primer_nombre,''),' ',COALESCE(guia.segundo_nombre,''),' ',COALESCE(guia.primer_apellido,''),' ',COALESCE(guia.segundo_apellido,'')) as docente_guia_nombre")
            )
            ->where('config_grupos.periodo_lectivo_id', $periodoId);

        if ($grupoId && $grupoId > 0) {
            $query->where('config_grupos.id', $grupoId);
        } elseif ($turnoId) {
            $query->where('config_grupos.turno_id', $turnoId);
        }

        $grupos = $query->orderBy('config_grado.orden')->orderBy('config_seccion.orden')->get();
        $periodo = DB::table('conf_periodo_lectivos')->find($periodoId);

        $reporteData = [];

        foreach ($grupos as $grupo) {
            // Fetch students directly from service
            $alumnos = $this->service->listarAlumnos($periodoId, $grupo->id, $turnoId);

            $f = 0;
            $m = 0;
            $total = 0;
            foreach ($alumnos as $row) {
                $row = (array)$row;
                $sx = strtoupper(trim($row['sexo'] ?? ''));
                if ($sx === 'F') {
                    $f++;
                } elseif ($sx === 'M') {
                    $m++;
                }
                $total++;
            }

            $reporteData[] = [
                'grupo' => $grupo,
                'alumnos' => $alumnos->map(function ($item) {
                    return (array)$item;
                }),
                'femenino' => $f,
                'masculino' => $m,
                'total_alumnos' => $total,
            ];
        }

        $html = view('reportes.listas_grupo.lista-alumnos', [
            'reporteData' => $reporteData,
            'periodo' => $periodo,
        ])->render();

        $pdf = SnappyPdf::loadHTML($html)
            ->setPaper('letter')
            ->setOrientation('portrait')
            ->setOption('margin-top', 10)
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 20)
            ->setOption('margin-left', 10)
            ->setOption('encoding', 'utf-8')
            ->setOption('enable-local-file-access', true)
            ->setOption('footer-left', 'Fecha y hora: [date] [time]')
            ->setOption('footer-right', 'Página [page] de [toPage]')
            ->setOption('footer-spacing', 5)
            ->setOption('load-error-handling', 'ignore');

        $nombreArchivo = 'lista_alumnos_' . (count($grupos) > 1 ? 'todos_' : '') . now()->format('Y-m-d_H-i-s') . '.pdf';
        return $pdf->download($nombreArchivo);
    }

    public function alumnosExcel(ListasGrupoRequest $request)
    {
        $periodoId = $request->input('periodo_lectivo_id');
        $grupoId = $request->input('grupo_id');
        $turnoId = $request->input('turno_id');

        // Reuse query logic (could extract to private method)
        $query = DB::table('config_grupos')
            ->join('config_grado', 'config_grupos.grado_id', '=', 'config_grado.id')
            ->join('config_seccion', 'config_grupos.seccion_id', '=', 'config_seccion.id')
            ->join('config_turnos', 'config_grupos.turno_id', '=', 'config_turnos.id')
            ->select('config_grupos.id', 'config_grado.nombre as grado', 'config_seccion.nombre as seccion', 'config_turnos.nombre as turno')
            ->where('config_grupos.periodo_lectivo_id', $periodoId);

        if ($grupoId && $grupoId > 0) {
            $query->where('config_grupos.id', $grupoId);
        } elseif ($turnoId) {
            $query->where('config_grupos.turno_id', $turnoId);
        }
        $grupos = $query->orderBy('config_grado.orden')->orderBy('config_seccion.orden')->get();
        $periodo = \DB::table('conf_periodo_lectivos')->find($periodoId);

        $sheets = [];

        foreach ($grupos as $grupo) {
            $alumnos = $this->service->listarAlumnos($periodoId, $grupo->id, $turnoId);

            $rows = [];
            $f = 0;
            $m = 0;
            $total = 0;
            foreach ($alumnos as $index => $row) {
                $row = (array)$row;
                $sx = strtoupper(trim($row['sexo'] ?? ''));
                $sxLabel = $sx === 'M' ? 'Masculino' : ($sx === 'F' ? 'Femenino' : '');
                if ($sx === 'F') {
                    $f++;
                } elseif ($sx === 'M') {
                    $m++;
                }
                $total++;

                $rows[] = [
                    $index + 1,
                    $row['nombre_completo'],
                    $row['correo'] ?? '',
                    $sxLabel,
                ];
            }

            // Stats row
            $rows[] = ['', 'Resumen:', '', ''];
            $rows[] = ['', 'Femenino', $f, ''];
            $rows[] = ['', 'Masculino', $m, ''];
            $rows[] = ['', 'Total', $total, ''];

            $groupLabel = ($grupo->grado ?? '') . ' - ' . ($grupo->seccion ?? '');
            // Excel sheet name limit is 31 chars
            $sheetName = mb_substr($groupLabel, 0, 31);
            // Ensure unique names if collision occurs (rare but possible with truncation)
            // A simple counter logic or ID appended suffix if needed, but for now simple trim.

            $sheets[] = [
                'name' => $sheetName,
                'meta' => [
                    ['Período:', $periodo->nombre ?? ''],
                    ['Grupo:', $groupLabel . ' (' . ($grupo->turno ?? '') . ')'],
                ],
                'headings' => ['#', 'Nombre Completo', 'Correo', 'Sexo'],
                'rows' => $rows
            ];
        }

        $binary = \App\Utils\SimpleXlsxGenerator::generateMultiSheet($sheets);

        return response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="lista_alumnos_grupos.xlsx"'
        ]);
    }
}
