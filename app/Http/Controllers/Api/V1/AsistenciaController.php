<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AsistenciaRequest;
use App\Services\AsistenciaService;
use App\Services\UsersGrupoService;
use App\Services\ListasGrupoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Barryvdh\Snappy\Facades\SnappyPdf;
use App\Utils\SimpleXlsxGenerator;
use App\Services\ConfPeriodoLectivoService;
use App\Services\ConfigGruposService;

class AsistenciaController extends Controller
{
    public function __construct(
        private AsistenciaService $service,
        private UsersGrupoService $usersGrupoService,
        private ListasGrupoService $listasGrupoService,
        private ConfPeriodoLectivoService $confPeriodoLectivoService,
        private ConfigGruposService $configGruposService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int)($request->query('per_page', 15));
        $data = $this->service->listarPaginado($perPage);
        return $this->successResponse($data, 'Asistencias listadas correctamente');
    }

    public function getAll(): JsonResponse
    {
        $data = $this->service->listarPaginado(1000);
        return $this->successResponse($data->items(), 'Asistencias obtenidas');
    }

    public function usuariosPorGrupo(int $grupoId): JsonResponse
    {
        $alumnos = $this->listasGrupoService->listarAlumnos(null, $grupoId, null);
        $data = collect($alumnos)->map(function ($row) {
            return [
                'id' => (int)($row->user_id ?? 0),
                'nombre' => (string)($row->nombre_completo ?? ''),
                'email' => $row->correo ?? null,
            ];
        });

        return $this->successResponse($data, 'Usuarios del grupo');
    }

    public function excepcionesPorFecha(int $grupoId, string $fecha, string $corte): JsonResponse
    {
        $data = $this->service->excepciones($grupoId, $fecha, $corte);
        return $this->successResponse($data, 'Excepciones obtenidas');
    }

    public function myActiveGroups(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) return $this->errorResponse('No autenticado', [], 401);

        $grupos = $this->configGruposService->getGruposDocenteGuiaActivo($user->id);

        $data = $grupos->map(function ($g) {
            return [
                'id' => $g->id,
                'grado' => $g->grado->nombre ?? '',
                'seccion' => $g->seccion->nombre ?? '',
                'turno' => $g->turno->nombre ?? '',
                'periodo' => $g->periodo_lectivo->nombre ?? '',
                'periodo_lectivo_id' => $g->periodo_lectivo_id,
            ];
        });

        return $this->successResponse($data, 'Mis grupos activos obtenidos');
    }

    public function fechasRegistradas(int $grupoId, string $corte): JsonResponse
    {
        $data = $this->service->obtenerFechasRegistradas($grupoId, $corte);
        return $this->successResponse($data, 'Fechas registradas obtenidas');
    }

    public function registrarGrupo(AsistenciaRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $creados = $this->service->registrarGrupo(
            (int)$validated['grupo_id'],
            (string)$validated['fecha'],
            (string)$validated['corte'],
            (array)$validated['excepciones']
        );

        return $this->successResponse($creados, 'Excepciones registradas', 201);
    }

    public function update(int $id, AsistenciaRequest $request): JsonResponse
    {
        $ok = $this->service->actualizar($id, $request->validated());
        return $this->successResponse(['updated' => $ok], 'Asistencia actualizada');
    }

    public function destroy(int $id): JsonResponse
    {
        $ok = $this->service->eliminar($id);
        return $this->successResponse(['deleted' => $ok], 'Excepción eliminada');
    }

    public function reportePorCorte(Request $request, int $grupoId, string $corte): JsonResponse
    {
        $data = $this->service->reportePorCorte($grupoId, $corte);
        return $this->successResponse($data, 'Reporte por corte');
    }

    public function reporteGeneral(Request $request, int $grupoId): JsonResponse
    {
        $data = $this->service->reporteGeneral($grupoId);
        return $this->successResponse($data, 'Reporte general por cortes');
    }

    public function exportReportePorCorte(Request $request, int $grupoId, string $corte): JsonResponse
    {
        $format = strtolower((string)$request->query('format', 'pdf'));
        $data = $this->service->reportePorCorte($grupoId, $corte);

        if ($format === 'xlsx') {
            $headings = ['ID', 'Nombre', 'Ausencias J', 'Ausencias I', 'Tardes J', 'Tardes I', '% Asistencia', '% Llegadas Tarde'];
            $rows = [];
            foreach ($data['usuarios'] as $u) {
                $rows[] = [
                    $u['user_id'],
                    $u['nombre'],
                    $u['ausencias_justificadas'],
                    $u['ausencias_injustificadas'],
                    $u['tardes_justificadas'],
                    $u['tardes_injustificadas'],
                    $u['porcentaje_asistencia'],
                    $u['porcentaje_llegada_tarde']
                ];
            }
            $content = SimpleXlsxGenerator::generate($headings, $rows);
            return response()->json([
                'success' => true,
                'data' => [
                    'filename' => 'Reporte_Asistencia_Corte.xlsx',
                    'content' => base64_encode($content)
                ],
                'message' => 'Exportación Excel generada'
            ]);
        }

        $html = '<html><head><meta charset="utf-8"><style>table{width:100%;border-collapse:collapse;margin-top:12px}thead{display:table-header-group}tbody{display:table-row-group}tfoot{display:table-footer-group}tr{page-break-inside:avoid}th,td{border:1px solid #ccc;padding:6px;text-align:left}</style></head><body>'
            . '<h2>Reporte de Asistencia por Corte</h2>'
            . '<table><thead><tr><th>Nombre</th><th>Ausencias J</th><th>Ausencias I</th><th>Tardes J</th><th>Tardes I</th><th>% Asistencia</th><th>% Llegadas Tarde</th></tr></thead><tbody>';
        foreach ($data['usuarios'] as $u) {
            $html .= '<tr><td>' . htmlspecialchars($u['nombre']) . '</td><td>' . $u['ausencias_justificadas'] . '</td><td>' . $u['ausencias_injustificadas'] . '</td><td>' . $u['tardes_justificadas'] . '</td><td>' . $u['tardes_injustificadas'] . '</td><td>' . $u['porcentaje_asistencia'] . "%</td><td>" . $u['porcentaje_llegada_tarde'] . "%</td></tr>";
        }
        $html .= '</tbody></table>'
            . '<h3>Totales</h3>'
            . '<p>Aus J: ' . $data['totales']['ausencias_justificadas'] . ' | Aus I: ' . $data['totales']['ausencias_injustificadas'] . ' | Tar J: ' . $data['totales']['tardes_justificadas'] . ' | Tar I: ' . $data['totales']['tardes_injustificadas'] . ' | Promedio Asistencia: ' . $data['totales']['promedio_asistencia'] . "% | Promedio Llegadas Tarde: " . $data['totales']['promedio_llegada_tarde'] . "%</p>";
        $html .= '</body></html>';

        $titulo = 'ASISTENCIAS - REPORTE POR CORTE';
        $grupo = $this->configGruposService->getConfigGruposById($grupoId);
        $nombreGrupo = (($grupo->grado->nombre ?? '') . ' - ' . ($grupo->seccion->nombre ?? ''));
        $turnoNombre = ($grupo->turno->nombre ?? '');
        $corteLabelMap = [
            'corte_1' => 'CORTE 1',
            'corte_2' => 'CORTE 2',
            'corte_3' => 'CORTE 3',
            'corte_4' => 'CORTE 4',
        ];
        $corteKey = strtolower((string)$corte);
        $corteLabel = $corteLabelMap[$corteKey] ?? strtoupper(str_replace('_', ' ', (string)$corte));
        $subtitulo1 = 'Grupo: ' . $nombreGrupo . ' | Turno: ' . $turnoNombre . ' | Corte: ' . $corteLabel;
        $subtitulo2 = 'Generado: ' . now()->format('d/m/Y H:i');
        $nombreInstitucion = config('app.nombre_institucion', 'INSTITUCIÓN');
        $headerHtml = view()->make('pdf.header', compact('titulo', 'subtitulo1', 'subtitulo2', 'nombreInstitucion'))->render();

        $pdf = SnappyPdf::loadHTML($html)
            ->setPaper('letter')
            ->setOrientation('portrait')
            ->setOption('margin-top', 50)
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 20)
            ->setOption('margin-left', 10)
            ->setOption('encoding', 'utf-8')
            ->setOption('enable-local-file-access', true)
            ->setOption('header-html', $headerHtml)
            ->setOption('header-spacing', 12)
            ->setOption('footer-left', 'Fecha y hora: [date] [time]')
            ->setOption('footer-right', 'Página [page] de [toPage]')
            ->setOption('footer-spacing', 8)
            ->setOption('load-error-handling', 'ignore');
        $content = $pdf->output();
        return response()->json([
            'success' => true,
            'data' => [
                'filename' => 'Reporte_Asistencia_Corte.pdf',
                'content' => base64_encode($content)
            ],
            'message' => 'Exportación PDF generada'
        ]);
    }

    public function exportReporteGeneral(Request $request, int $grupoId): JsonResponse
    {
        $format = strtolower((string)$request->query('format', 'pdf'));
        $data = $this->service->reporteGeneral($grupoId);

        if ($format === 'xlsx') {
            $headings = [
                'Alumno',
                'C1 AJ',
                'C1 AI',
                'C1 LLT',
                'C1 LLTI',
                'C1 %A',
                'C1 %LLT',
                'C2 AJ',
                'C2 AI',
                'C2 LLT',
                'C2 LLTI',
                'C2 %A',
                'C2 %LLT',
                'C3 AJ',
                'C3 AI',
                'C3 LLT',
                'C3 LLTI',
                'C3 %A',
                'C3 %LLT',
                'C4 AJ',
                'C4 AI',
                'C4 LLT',
                'C4 LLTI',
                'C4 %A',
                'C4 %LLT',
                'PROM %A',
                'PROM %LLT'
            ];
            $rows = [];
            foreach (($data['alumnos'] ?? []) as $al) {
                $get = function ($alumno, $corte, $key) {
                    return isset($alumno['cortes'][$corte][$key]) ? $alumno['cortes'][$corte][$key] : '';
                };
                $rows[] = [
                    (string)($al['nombre'] ?? ''),
                    $get($al, 'corte_1', 'ausencias_justificadas'),
                    $get($al, 'corte_1', 'ausencias_injustificadas'),
                    $get($al, 'corte_1', 'tardes_justificadas'),
                    $get($al, 'corte_1', 'tardes_injustificadas'),
                    $get($al, 'corte_1', 'porcentaje_asistencia'),
                    $get($al, 'corte_1', 'porcentaje_llegada_tarde'),
                    $get($al, 'corte_2', 'ausencias_justificadas'),
                    $get($al, 'corte_2', 'ausencias_injustificadas'),
                    $get($al, 'corte_2', 'tardes_justificadas'),
                    $get($al, 'corte_2', 'tardes_injustificadas'),
                    $get($al, 'corte_2', 'porcentaje_asistencia'),
                    $get($al, 'corte_2', 'porcentaje_llegada_tarde'),
                    $get($al, 'corte_3', 'ausencias_justificadas'),
                    $get($al, 'corte_3', 'ausencias_injustificadas'),
                    $get($al, 'corte_3', 'tardes_justificadas'),
                    $get($al, 'corte_3', 'tardes_injustificadas'),
                    $get($al, 'corte_3', 'porcentaje_asistencia'),
                    $get($al, 'corte_3', 'porcentaje_llegada_tarde'),
                    $get($al, 'corte_4', 'ausencias_justificadas'),
                    $get($al, 'corte_4', 'ausencias_injustificadas'),
                    $get($al, 'corte_4', 'tardes_justificadas'),
                    $get($al, 'corte_4', 'tardes_injustificadas'),
                    $get($al, 'corte_4', 'porcentaje_asistencia'),
                    $get($al, 'corte_4', 'porcentaje_llegada_tarde'),
                    (float)($al['promedio_asistencia'] ?? 0),
                    (float)($al['promedio_llegada_tarde'] ?? 0)
                ];
            }
            // Fila de promedio total (solo %A y %LLT por corte y global)
            $promRow = ['PROMEDIO TOTAL'];
            foreach (['corte_1', 'corte_2', 'corte_3', 'corte_4'] as $c) {
                $promRow[] = ''; // AJ
                $promRow[] = ''; // AI
                $promRow[] = ''; // LLT
                $promRow[] = ''; // LLTI
                $promRow[] = isset($data['por_corte'][$c]['totales']['promedio_asistencia']) ? $data['por_corte'][$c]['totales']['promedio_asistencia'] : '';
                $promRow[] = isset($data['por_corte'][$c]['totales']['promedio_llegada_tarde']) ? $data['por_corte'][$c]['totales']['promedio_llegada_tarde'] : '';
            }
            $promRow[] = (float)($data['promedio_general_asistencia'] ?? 0);
            $promRow[] = (float)($data['promedio_general_llegada_tarde'] ?? 0);
            $rows[] = $promRow;

            $content = SimpleXlsxGenerator::generate($headings, $rows);
            return response()->json([
                'success' => true,
                'data' => [
                    'filename' => 'Reporte_Asistencia_General.xlsx',
                    'content' => base64_encode($content)
                ],
                'message' => 'Exportación Excel generada'
            ]);
        }

        $html = '<html><head><meta charset="utf-8"><style>table{width:100%;border-collapse:collapse;margin-top:12px}thead{display:table-header-group}tbody{display:table-row-group}tfoot{display:table-footer-group}tr{page-break-inside:avoid}th,td{border:1px solid #ccc;padding:6px;text-align:center}th{background:#f5f5f5}</style></head><body>'
            . '<h2>Reporte General de Asistencias</h2>'
            . '<table><thead>'
            . '<tr>'
            . '<th rowspan="2">Alumno</th>'
            . '<th colspan="6">Corte 1</th>'
            . '<th colspan="6">Corte 2</th>'
            . '<th colspan="6">Corte 3</th>'
            . '<th colspan="6">Corte 4</th>'
            . '<th colspan="2">Prom</th>'
            . '</tr>'
            . '<tr>'
            . '<th>AJ</th><th>AI</th><th>LLT</th><th>LLTI</th><th>%A</th><th>%LLT</th>'
            . '<th>AJ</th><th>AI</th><th>LLT</th><th>LLTI</th><th>%A</th><th>%LLT</th>'
            . '<th>AJ</th><th>AI</th><th>LLT</th><th>LLTI</th><th>%A</th><th>%LLT</th>'
            . '<th>AJ</th><th>AI</th><th>LLT</th><th>LLTI</th><th>%A</th><th>%LLT</th>'
            . '<th>%A</th><th>%LLT</th>'
            . '</tr>'
            . '</thead><tbody>';
        foreach (($data['alumnos'] ?? []) as $al) {
            $get = function ($alumno, $corte, $key) {
                return isset($alumno['cortes'][$corte][$key]) ? $alumno['cortes'][$corte][$key] : '';
            };
            $html .= '<tr><td>' . htmlspecialchars((string)($al['nombre'] ?? '')) . '</td>'
                . '<td>' . $get($al, 'corte_1', 'ausencias_justificadas') . '</td><td>' . $get($al, 'corte_1', 'ausencias_injustificadas') . '</td><td>' . $get($al, 'corte_1', 'tardes_justificadas') . '</td><td>' . $get($al, 'corte_1', 'tardes_injustificadas') . '</td><td>' . $get($al, 'corte_1', 'porcentaje_asistencia') . '%</td><td>' . $get($al, 'corte_1', 'porcentaje_llegada_tarde') . '%</td>'
                . '<td>' . $get($al, 'corte_2', 'ausencias_justificadas') . '</td><td>' . $get($al, 'corte_2', 'ausencias_injustificadas') . '</td><td>' . $get($al, 'corte_2', 'tardes_justificadas') . '</td><td>' . $get($al, 'corte_2', 'tardes_injustificadas') . '</td><td>' . $get($al, 'corte_2', 'porcentaje_asistencia') . '%</td><td>' . $get($al, 'corte_2', 'porcentaje_llegada_tarde') . '%</td>'
                . '<td>' . $get($al, 'corte_3', 'ausencias_justificadas') . '</td><td>' . $get($al, 'corte_3', 'ausencias_injustificadas') . '</td><td>' . $get($al, 'corte_3', 'tardes_justificadas') . '</td><td>' . $get($al, 'corte_3', 'tardes_injustificadas') . '</td><td>' . $get($al, 'corte_3', 'porcentaje_asistencia') . '%</td><td>' . $get($al, 'corte_3', 'porcentaje_llegada_tarde') . '%</td>'
                . '<td>' . $get($al, 'corte_4', 'ausencias_justificadas') . '</td><td>' . $get($al, 'corte_4', 'ausencias_injustificadas') . '</td><td>' . $get($al, 'corte_4', 'tardes_justificadas') . '</td><td>' . $get($al, 'corte_4', 'tardes_injustificadas') . '</td><td>' . $get($al, 'corte_4', 'porcentaje_asistencia') . '%</td><td>' . $get($al, 'corte_4', 'porcentaje_llegada_tarde') . '%</td>'
                . '<td>' . ($al['promedio_asistencia'] ?? 0) . '%</td><td>' . ($al['promedio_llegada_tarde'] ?? 0) . '%</td></tr>';
        }
        // Fila de promedio total (amarillo)
        $html .= '<tr style="background:#ff0;font-weight:bold"><td>PROMEDIO TOTAL</td>';
        foreach (['corte_1', 'corte_2', 'corte_3', 'corte_4'] as $c) {
            $promA = isset($data['por_corte'][$c]['totales']['promedio_asistencia']) ? $data['por_corte'][$c]['totales']['promedio_asistencia'] : '';
            $promT = isset($data['por_corte'][$c]['totales']['promedio_llegada_tarde']) ? $data['por_corte'][$c]['totales']['promedio_llegada_tarde'] : '';
            $html .= '<td></td><td></td><td></td><td></td><td>' . $promA . '%</td><td>' . $promT . '%</td>';
        }
        $html .= '<td>' . ($data['promedio_general_asistencia'] ?? 0) . '%</td><td>' . ($data['promedio_general_llegada_tarde'] ?? 0) . '%</td></tr>';
        $html .= '</tbody></table>';
        $html .= '</body></html>';

        $titulo = 'ASISTENCIAS - REPORTE GENERAL POR CORTES';
        $grupo = $this->configGruposService->getConfigGruposById($grupoId);
        $nombreGrupo = (($grupo->grado->nombre ?? '') . ' - ' . ($grupo->seccion->nombre ?? ''));
        $turnoNombre = ($grupo->turno->nombre ?? '');
        $subtitulo1 = 'Grupo: ' . $nombreGrupo . ' | Turno: ' . $turnoNombre;
        $subtitulo2 = 'Generado: ' . now()->format('d/m/Y H:i');
        $nombreInstitucion = config('app.nombre_institucion', 'INSTITUCIÓN');
        $headerHtml = view()->make('pdf.header', compact('titulo', 'subtitulo1', 'subtitulo2', 'nombreInstitucion'))->render();

        $pdf = SnappyPdf::loadHTML($html)
            ->setPaper('letter')
            ->setOrientation('portrait')
            ->setOption('margin-top', 50)
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 20)
            ->setOption('margin-left', 10)
            ->setOption('encoding', 'utf-8')
            ->setOption('enable-local-file-access', true)
            ->setOption('header-html', $headerHtml)
            ->setOption('header-spacing', 12)
            ->setOption('footer-left', 'Fecha y hora: [date] [time]')
            ->setOption('footer-right', 'Página [page] de [toPage]')
            ->setOption('footer-spacing', 8)
            ->setOption('load-error-handling', 'ignore');
        $content = $pdf->output();
        return response()->json([
            'success' => true,
            'data' => [
                'filename' => 'Reporte_Asistencia_General.pdf',
                'content' => base64_encode($content)
            ],
            'message' => 'Exportación PDF generada'
        ]);
    }

    public function reporteGeneralPorGrupo(Request $request): JsonResponse
    {
        $periodoId = (int) $request->query('periodo_lectivo_id');
        $data = $this->service->reporteGeneralPorGrupo($periodoId);
        return $this->successResponse($data, 'Reporte general por grupo');
    }

    public function exportReporteGeneralPorGrupo(Request $request): JsonResponse
    {
        $format = strtolower((string)$request->query('format', 'pdf'));
        $periodoId = (int) $request->query('periodo_lectivo_id');
        $data = $this->service->reporteGeneralPorGrupo($periodoId);

        if ($format === 'xlsx') {
            $headings = [
                'Grupo',
                'Turno',
                'C1 AJ',
                'C1 AI',
                'C1 LLT',
                'C1 LLTI',
                'C1 %A',
                'C1 %LLT',
                'C2 AJ',
                'C2 AI',
                'C2 LLT',
                'C2 LLTI',
                'C2 %A',
                'C2 %LLT',
                'C3 AJ',
                'C3 AI',
                'C3 LLT',
                'C3 LLTI',
                'C3 %A',
                'C3 %LLT',
                'C4 AJ',
                'C4 AI',
                'C4 LLT',
                'C4 LLTI',
                'C4 %A',
                'C4 %LLT',
                'PROM %A',
                'PROM %LLT'
            ];
            $rows = [];
            foreach (($data['rows'] ?? []) as $row) {
                $get = function ($r, $corte, $key) {
                    return $r['cortes'][$corte][$key] ?? '';
                };
                $rows[] = [
                    (string)($row['grupo'] ?? ''),
                    (string)($row['turno'] ?? ''),
                    $get($row, 'corte_1', 'AJ'),
                    $get($row, 'corte_1', 'AI'),
                    $get($row, 'corte_1', 'LLT'),
                    $get($row, 'corte_1', 'LLTI'),
                    $get($row, 'corte_1', '%A'),
                    $get($row, 'corte_1', '%LLT'),
                    $get($row, 'corte_2', 'AJ'),
                    $get($row, 'corte_2', 'AI'),
                    $get($row, 'corte_2', 'LLT'),
                    $get($row, 'corte_2', 'LLTI'),
                    $get($row, 'corte_2', '%A'),
                    $get($row, 'corte_2', '%LLT'),
                    $get($row, 'corte_3', 'AJ'),
                    $get($row, 'corte_3', 'AI'),
                    $get($row, 'corte_3', 'LLT'),
                    $get($row, 'corte_3', 'LLTI'),
                    $get($row, 'corte_3', '%A'),
                    $get($row, 'corte_3', '%LLT'),
                    $get($row, 'corte_4', 'AJ'),
                    $get($row, 'corte_4', 'AI'),
                    $get($row, 'corte_4', 'LLT'),
                    $get($row, 'corte_4', 'LLTI'),
                    $get($row, 'corte_4', '%A'),
                    $get($row, 'corte_4', '%LLT'),
                    (float)($row['promedio_asistencia'] ?? 0),
                    (float)($row['promedio_llegada_tarde'] ?? 0)
                ];
            }
            $promRow = ['PROMEDIO TOTAL', ''];
            foreach (['corte_1', 'corte_2', 'corte_3', 'corte_4'] as $c) {
                $promRow[] = '';
                $promRow[] = '';
                $promRow[] = '';
                $promRow[] = '';
                $promRow[] = $data['promedio_total_por_corte'][$c]['%A'] ?? '';
                $promRow[] = $data['promedio_total_por_corte'][$c]['%LLT'] ?? '';
            }
            $promRow[] = (float)($data['promedio_general_asistencia'] ?? 0);
            $promRow[] = (float)($data['promedio_general_llegada_tarde'] ?? 0);
            $rows[] = $promRow;

            $content = SimpleXlsxGenerator::generate($headings, $rows);
            return response()->json([
                'success' => true,
                'data' => [
                    'filename' => 'Reporte_Asistencia_General_Por_Grupo.xlsx',
                    'content' => base64_encode($content)
                ],
                'message' => 'Exportación Excel generada'
            ]);
        }

        $html = '<html><head><meta charset="utf-8"><style>table{width:100%;border-collapse:collapse;margin-top:12px}thead{display:table-header-group}tbody{display:table-row-group}tfoot{display:table-footer-group}tr{page-break-inside:avoid}th,td{border:1px solid #ccc;padding:6px;text-align:center}th{background:#f5f5f5}</style></head><body>'
            . '<h2>Reporte General de Asistencias por Grupo</h2>'
            . '<table><thead>'
            . '<tr>'
            . '<th rowspan="2">Grupo</th><th rowspan="2">Turno</th>'
            . '<th colspan="6">Corte 1</th>'
            . '<th colspan="6">Corte 2</th>'
            . '<th colspan="6">Corte 3</th>'
            . '<th colspan="6">Corte 4</th>'
            . '<th colspan="2">Prom</th>'
            . '</tr>'
            . '<tr>'
            . '<th>AJ</th><th>AI</th><th>LLT</th><th>LLTI</th><th>%A</th><th>%LLT</th>'
            . '<th>AJ</th><th>AI</th><th>LLT</th><th>LLTI</th><th>%A</th><th>%LLT</th>'
            . '<th>AJ</th><th>AI</th><th>LLT</th><th>LLTI</th><th>%A</th><th>%LLT</th>'
            . '<th>AJ</th><th>AI</th><th>LLT</th><th>LLTI</th><th>%A</th><th>%LLT</th>'
            . '<th>%A</th><th>%LLT</th>'
            . '</tr>'
            . '</thead><tbody>';
        foreach (($data['rows'] ?? []) as $row) {
            $get = function ($r, $corte, $key) {
                return $r['cortes'][$corte][$key] ?? '';
            };
            $html .= '<tr><td>' . htmlspecialchars((string)($row['grupo'] ?? '')) . '</td><td>' . htmlspecialchars((string)($row['turno'] ?? '')) . '</td>'
                . '<td>' . $get($row, 'corte_1', 'AJ') . '</td><td>' . $get($row, 'corte_1', 'AI') . '</td><td>' . $get($row, 'corte_1', 'LLT') . '</td><td>' . $get($row, 'corte_1', 'LLTI') . '</td><td>' . $get($row, 'corte_1', '%A') . '%</td><td>' . $get($row, 'corte_1', '%LLT') . '%</td>'
                . '<td>' . $get($row, 'corte_2', 'AJ') . '</td><td>' . $get($row, 'corte_2', 'AI') . '</td><td>' . $get($row, 'corte_2', 'LLT') . '</td><td>' . $get($row, 'corte_2', 'LLTI') . '</td><td>' . $get($row, 'corte_2', '%A') . '%</td><td>' . $get($row, 'corte_2', '%LLT') . '%</td>'
                . '<td>' . $get($row, 'corte_3', 'AJ') . '</td><td>' . $get($row, 'corte_3', 'AI') . '</td><td>' . $get($row, 'corte_3', 'LLT') . '</td><td>' . $get($row, 'corte_3', 'LLTI') . '</td><td>' . $get($row, 'corte_3', '%A') . '%</td><td>' . $get($row, 'corte_3', '%LLT') . '%</td>'
                . '<td>' . $get($row, 'corte_4', 'AJ') . '</td><td>' . $get($row, 'corte_4', 'AI') . '</td><td>' . $get($row, 'corte_4', 'LLT') . '</td><td>' . $get($row, 'corte_4', 'LLTI') . '</td><td>' . $get($row, 'corte_4', '%A') . '%</td><td>' . $get($row, 'corte_4', '%LLT') . '%</td>'
                . '<td>' . ($row['promedio_asistencia'] ?? 0) . '%</td><td>' . ($row['promedio_llegada_tarde'] ?? 0) . '%</td></tr>';
        }
        $html .= '<tr style="background:#ff0;font-weight:bold"><td>PROMEDIO TOTAL</td><td></td>';
        foreach (['corte_1', 'corte_2', 'corte_3', 'corte_4'] as $c) {
            $promA = $data['promedio_total_por_corte'][$c]['%A'] ?? '';
            $promT = $data['promedio_total_por_corte'][$c]['%LLT'] ?? '';
            $html .= '<td></td><td></td><td></td><td></td><td>' . $promA . '%</td><td>' . $promT . '%</td>';
        }
        $html .= '<td>' . ($data['promedio_general_asistencia'] ?? 0) . '%</td><td>' . ($data['promedio_general_llegada_tarde'] ?? 0) . '%</td></tr>';
        $html .= '</tbody></table>';

        $titulo = 'ASISTENCIAS - REPORTE GENERAL POR GRUPO';
        $subtitulo1 = null;
        $subtitulo2 = 'Generado: ' . now()->format('d/m/Y H:i');
        $nombreInstitucion = config('app.nombre_institucion', 'INSTITUCIÓN');
        $headerHtml = view()->make('pdf.header', compact('titulo', 'subtitulo1', 'subtitulo2', 'nombreInstitucion'))->render();

        $pdf = SnappyPdf::loadHTML($html)
            ->setPaper('letter')
            ->setOrientation('portrait')
            ->setOption('margin-top', 50)
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 20)
            ->setOption('margin-left', 10)
            ->setOption('encoding', 'utf-8')
            ->setOption('enable-local-file-access', true)
            ->setOption('header-html', $headerHtml)
            ->setOption('header-spacing', 12)
            ->setOption('footer-left', 'Fecha y hora: [date] [time]')
            ->setOption('footer-right', 'Página [page] de [toPage]')
            ->setOption('footer-spacing', 8)
            ->setOption('load-error-handling', 'ignore');
        $content = $pdf->output();
        return response()->json([
            'success' => true,
            'data' => [
                'filename' => 'Reporte_Asistencia_General_Por_Grupo.pdf',
                'content' => base64_encode($content)
            ],
            'message' => 'Exportación PDF generada'
        ]);
    }

    public function reporteGeneralPorGrado(Request $request): JsonResponse
    {
        $periodoId = (int) $request->query('periodo_lectivo_id');
        $data = $this->service->reporteGeneralPorGrado($periodoId);
        return $this->successResponse($data, 'Reporte general por grado');
    }

    public function exportReporteGeneralPorGrado(Request $request): JsonResponse
    {
        $format = strtolower((string)$request->query('format', 'pdf'));
        $periodoId = (int) $request->query('periodo_lectivo_id');
        $data = $this->service->reporteGeneralPorGrado($periodoId);

        if ($format === 'xlsx') {
            $headings = [
                'Grado',
                'Turno',
                'C1 AJ',
                'C1 AI',
                'C1 LLT',
                'C1 LLTI',
                'C1 %A',
                'C1 %LLT',
                'C2 AJ',
                'C2 AI',
                'C2 LLT',
                'C2 LLTI',
                'C2 %A',
                'C2 %LLT',
                'C3 AJ',
                'C3 AI',
                'C3 LLT',
                'C3 LLTI',
                'C3 %A',
                'C3 %LLT',
                'C4 AJ',
                'C4 AI',
                'C4 LLT',
                'C4 LLTI',
                'C4 %A',
                'C4 %LLT',
                'PROM %A',
                'PROM %LLT'
            ];
            $rows = [];
            foreach (($data['rows'] ?? []) as $row) {
                $get = function ($r, $corte, $key) {
                    return $r['cortes'][$corte][$key] ?? '';
                };
                $rows[] = [
                    (string)($row['grado'] ?? ''),
                    (string)($row['turno'] ?? ''),
                    $get($row, 'corte_1', 'AJ'),
                    $get($row, 'corte_1', 'AI'),
                    $get($row, 'corte_1', 'LLT'),
                    $get($row, 'corte_1', 'LLTI'),
                    $get($row, 'corte_1', '%A'),
                    $get($row, 'corte_1', '%LLT'),
                    $get($row, 'corte_2', 'AJ'),
                    $get($row, 'corte_2', 'AI'),
                    $get($row, 'corte_2', 'LLT'),
                    $get($row, 'corte_2', 'LLTI'),
                    $get($row, 'corte_2', '%A'),
                    $get($row, 'corte_2', '%LLT'),
                    $get($row, 'corte_3', 'AJ'),
                    $get($row, 'corte_3', 'AI'),
                    $get($row, 'corte_3', 'LLT'),
                    $get($row, 'corte_3', 'LLTI'),
                    $get($row, 'corte_3', '%A'),
                    $get($row, 'corte_3', '%LLT'),
                    $get($row, 'corte_4', 'AJ'),
                    $get($row, 'corte_4', 'AI'),
                    $get($row, 'corte_4', 'LLT'),
                    $get($row, 'corte_4', 'LLTI'),
                    $get($row, 'corte_4', '%A'),
                    $get($row, 'corte_4', '%LLT'),
                    (float)($row['promedio_asistencia'] ?? 0),
                    (float)($row['promedio_llegada_tarde'] ?? 0)
                ];
            }
            $promRow = ['PROMEDIO TOTAL', ''];
            foreach (['corte_1', 'corte_2', 'corte_3', 'corte_4'] as $c) {
                $promRow[] = '';
                $promRow[] = '';
                $promRow[] = '';
                $promRow[] = '';
                $promRow[] = $data['promedio_total_por_corte'][$c]['%A'] ?? '';
                $promRow[] = $data['promedio_total_por_corte'][$c]['%LLT'] ?? '';
            }
            $promRow[] = (float)($data['promedio_general_asistencia'] ?? 0);
            $promRow[] = (float)($data['promedio_general_llegada_tarde'] ?? 0);
            $rows[] = $promRow;

            $content = SimpleXlsxGenerator::generate($headings, $rows);
            return response()->json([
                'success' => true,
                'data' => [
                    'filename' => 'Reporte_Asistencia_General_Por_Grado.xlsx',
                    'content' => base64_encode($content)
                ],
                'message' => 'Exportación Excel generada'
            ]);
        }

        $html = '<html><head><meta charset="utf-8"><style>table{width:100%;border-collapse:collapse;margin-top:12px}thead{display:table-header-group}tbody{display:table-row-group}tfoot{display:table-footer-group}tr{page-break-inside:avoid}th,td{border:1px solid #ccc;padding:6px;text-align:center}th{background:#f5f5f5}</style></head><body>'
            . '<h2>Reporte General de Asistencias por Grado</h2>'
            . '<table><thead>'
            . '<tr>'
            . '<th rowspan="2">Grado</th><th rowspan="2">Turno</th>'
            . '<th colspan="6">Corte 1</th>'
            . '<th colspan="6">Corte 2</th>'
            . '<th colspan="6">Corte 3</th>'
            . '<th colspan="6">Corte 4</th>'
            . '<th colspan="2">Prom</th>'
            . '</tr>'
            . '<tr>'
            . '<th>AJ</th><th>AI</th><th>LLT</th><th>LLTI</th><th>%A</th><th>%LLT</th>'
            . '<th>AJ</th><th>AI</th><th>LLT</th><th>LLTI</th><th>%A</th><th>%LLT</th>'
            . '<th>AJ</th><th>AI</th><th>LLT</th><th>LLTI</th><th>%A</th><th>%LLT</th>'
            . '<th>AJ</th><th>AI</th><th>LLT</th><th>LLTI</th><th>%A</th><th>%LLT</th>'
            . '<th>%A</th><th>%LLT</th>'
            . '</tr>'
            . '</thead><tbody>';
        foreach (($data['rows'] ?? []) as $row) {
            $get = function ($r, $corte, $key) {
                return $r['cortes'][$corte][$key] ?? '';
            };
            $html .= '<tr><td>' . htmlspecialchars((string)($row['grado'] ?? '')) . '</td><td>' . htmlspecialchars((string)($row['turno'] ?? '')) . '</td>'
                . '<td>' . $get($row, 'corte_1', 'AJ') . '</td><td>' . $get($row, 'corte_1', 'AI') . '</td><td>' . $get($row, 'corte_1', 'LLT') . '</td><td>' . $get($row, 'corte_1', 'LLTI') . '</td><td>' . $get($row, 'corte_1', '%A') . '%</td><td>' . $get($row, 'corte_1', '%LLT') . '%</td>'
                . '<td>' . $get($row, 'corte_2', 'AJ') . '</td><td>' . $get($row, 'corte_2', 'AI') . '</td><td>' . $get($row, 'corte_2', 'LLT') . '</td><td>' . $get($row, 'corte_2', 'LLTI') . '</td><td>' . $get($row, 'corte_2', '%A') . '%</td><td>' . $get($row, 'corte_2', '%LLT') . '%</td>'
                . '<td>' . $get($row, 'corte_3', 'AJ') . '</td><td>' . $get($row, 'corte_3', 'AI') . '</td><td>' . $get($row, 'corte_3', 'LLT') . '</td><td>' . $get($row, 'corte_3', 'LLTI') . '</td><td>' . $get($row, 'corte_3', '%A') . '%</td><td>' . $get($row, 'corte_3', '%LLT') . '%</td>'
                . '<td>' . $get($row, 'corte_4', 'AJ') . '</td><td>' . $get($row, 'corte_4', 'AI') . '</td><td>' . $get($row, 'corte_4', 'LLT') . '</td><td>' . $get($row, 'corte_4', 'LLTI') . '</td><td>' . $get($row, 'corte_4', '%A') . '%</td><td>' . $get($row, 'corte_4', '%LLT') . '%</td>'
                . '<td>' . ($row['promedio_asistencia'] ?? 0) . '%</td><td>' . ($row['promedio_llegada_tarde'] ?? 0) . '%</td></tr>';
        }
        $html .= '<tr style="background:#ff0;font-weight:bold"><td>PROMEDIO TOTAL</td><td></td>';
        foreach (['corte_1', 'corte_2', 'corte_3', 'corte_4'] as $c) {
            $promA = $data['promedio_total_por_corte'][$c]['%A'] ?? '';
            $promT = $data['promedio_total_por_corte'][$c]['%LLT'] ?? '';
            $html .= '<td></td><td></td><td></td><td></td><td>' . $promA . '%</td><td>' . $promT . '%</td>';
        }
        $html .= '<td>' . ($data['promedio_general_asistencia'] ?? 0) . '%</td><td>' . ($data['promedio_general_llegada_tarde'] ?? 0) . '%</td></tr>';
        $html .= '</tbody></table>';

        $titulo = 'ASISTENCIAS - REPORTE GENERAL POR GRADO';
        $subtitulo1 = null;
        $subtitulo2 = 'Generado: ' . now()->format('d/m/Y H:i');
        $nombreInstitucion = config('app.nombre_institucion', 'INSTITUCIÓN');
        $headerHtml = view()->make('pdf.header', compact('titulo', 'subtitulo1', 'subtitulo2', 'nombreInstitucion'))->render();

        $pdf = SnappyPdf::loadHTML($html)
            ->setPaper('letter')
            ->setOrientation('portrait')
            ->setOption('margin-top', 50)
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 20)
            ->setOption('margin-left', 10)
            ->setOption('encoding', 'utf-8')
            ->setOption('enable-local-file-access', true)
            ->setOption('header-html', $headerHtml)
            ->setOption('header-spacing', 12)
            ->setOption('footer-left', 'Fecha y hora: [date] [time]')
            ->setOption('footer-right', 'Página [page] de [toPage]')
            ->setOption('footer-spacing', 8)
            ->setOption('load-error-handling', 'ignore');
        $content = $pdf->output();
        return response()->json([
            'success' => true,
            'data' => [
                'filename' => 'Reporte_Asistencia_General_Por_Grado.pdf',
                'content' => base64_encode($content)
            ],
            'message' => 'Exportación PDF generada'
        ]);
    }

    public function periodosLectivos(): JsonResponse
    {
        $periodos = $this->confPeriodoLectivoService
            ->getAllConfPeriodoLectivos()
            ->sortByDesc('nombre')
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'nombre' => $p->nombre,
                ];
            })
            ->values();
        return $this->successResponse($periodos, 'Periodos lectivos obtenidos');
    }

    public function gruposPorTurno(Request $request): JsonResponse
    {
        $periodoId = (int)$request->query('periodo_id');
        $grupos = $this->configGruposService->getGruposByPeriodoLectivo($periodoId);

        $agrupados = [];
        foreach ($grupos as $g) {
            $turnoNombre = $g->turno?->nombre ?? 'Sin turno';
            if (!isset($agrupados[$turnoNombre])) {
                $agrupados[$turnoNombre] = [];
            }
            $agrupados[$turnoNombre][] = ['id' => $g->id, 'nombre' => $g->grado->nombre . ' - ' . $g->seccion->nombre];
        }

        return $this->successResponse($agrupados, 'Grupos agrupados por turno');
    }

    public function reporteSemanalPorGrupoYAlumno(Request $request): JsonResponse
    {
        $grupoId = (int)$request->query('grupo_id');
        $fechaInicio = (string)$request->query('fecha_inicio');
        $fechaFin = (string)$request->query('fecha_fin');
        $format = strtolower((string)$request->query('export', 'json'));

        $data = $this->service->reporteSemanalPorGrupoYAlumno($grupoId, $fechaInicio, $fechaFin);

        if ($format === 'json') {
            return $this->successResponse($data, 'Reporte semanal por grupo y alumno');
        }

        if ($format === 'xlsx') {
            $headings = ['#', 'Alumno'];
            $subHeadings = ['', ''];
            $merges = [];

            // Agrupar por semanas para los headers
            $semanas = [];
            foreach ($data['fechas'] as $f) {
                $date = \Carbon\Carbon::parse($f);
                $key = $date->format('o-W'); // Año ISO y número de semana

                if (!isset($semanas[$key])) {
                    $sw = $date->copy()->startOfWeek();
                    $ew = $date->copy()->endOfWeek();
                    $semanas[$key] = [
                        'inicio' => $sw->format('d/m'),
                        'fin' => $ew->format('d/m'),
                        'etiqueta' => 'DEL ' . $sw->format('d/m') . ' AL ' . $ew->format('d/m'),
                        'dias' => []
                    ];
                }
                $d = ['1' => 'L', '2' => 'K', '3' => 'M', '4' => 'J', '5' => 'V', '6' => 'S', '0' => 'D'][$date->format('w')];
                $semanas[$key]['dias'][] = [
                    'fecha' => $f,
                    'dia' => $d,
                    'dia_mes' => $date->format('d')
                ];
            }

            $colIndex = count($headings) + 1;
            foreach ($semanas as $sem) {
                $headings[] = rtrim($sem['etiqueta']);
                $startCol = $this->getColLetter($colIndex);
                foreach ($sem['dias'] as $i => $d) {
                    if ($i > 0) $headings[] = '';
                    $subHeadings[] = $d['dia'] . "\n" . $d['dia_mes'];
                    $colIndex++;
                }
                $endCol = $this->getColLetter($colIndex - 1);
                if ($startCol !== $endCol) {
                    $merges[] = "{$startCol}1:{$endCol}1";
                }
            }

            $headings = array_merge($headings, ['H. ASIST', 'H. INAS', 'H. JUS', 'H. TAR', 'PER.']);
            $subHeadings = array_merge($subHeadings, ['', '', '', '', '']);

            $rows = [$headings, $subHeadings];

            $count = 1;
            foreach ($data['alumnos'] as $al) {
                $row = [$count++, htmlspecialchars($al['nombre'])];
                foreach ($data['fechas'] as $f) {
                    $val = $al['dias'][$f] ?? '-';
                    // Traducción simple para exportar
                    if ($val === 'p') $val = '•';
                    elseif ($val === 'A') $val = 'F';
                    elseif ($val === 'J') $val = 'J';
                    elseif ($val === 'T') $val = 'T';
                    elseif ($val === 'Permiso') $val = 'P';
                    elseif ($val === 'Suspendido') $val = 'S';
                    $row[] = $val;
                }
                $row[] = $al['totales']['presentes'];
                $row[] = $al['totales']['ausentes'];
                $row[] = $al['totales']['justificados'];
                $row[] = $al['totales']['tardanzas'];
                $row[] = $al['totales']['permisos'] + $al['totales']['suspendidos'];
                $rows[] = $row;
            }

            // Totales por día
            $rowTotP = ['', 'T. de Estudiantes Presentes'];
            $rowTotA = ['', 'T. de Estudiantes Ausentes'];
            $rowTotJ = ['', 'T. de Estudiantes Justificados'];
            $rowM = ['', 'Varones Presentes'];
            $rowF = ['', 'Mujeres Presentes'];

            foreach ($data['fechas'] as $f) {
                $t = $data['totales_por_dia'][$f] ?? null;
                if ($t) {
                    $rowTotP[] = $t['presentes'] > 0 ? $t['presentes'] : '';
                    $rowTotA[] = $t['ausentes'] > 0 ? $t['ausentes'] : '';
                    $rowTotJ[] = $t['justificados'] > 0 ? $t['justificados'] : '';
                    $rowM[] = $t['m_presentes'] > 0 ? $t['m_presentes'] : '';
                    $rowF[] = $t['f_presentes'] > 0 ? $t['f_presentes'] : '';
                } else {
                    $rowTotP[] = '';
                    $rowTotA[] = '';
                    $rowTotJ[] = '';
                    $rowM[] = '';
                    $rowF[] = '';
                }
            }
            $rowTotP = array_merge($rowTotP, ['', '', '', '', '']);
            $rowTotA = array_merge($rowTotA, ['', '', '', '', '']);
            $rowTotJ = array_merge($rowTotJ, ['', '', '', '', '']);
            $rowM = array_merge($rowM, ['', '', '', '', '']);
            $rowF = array_merge($rowF, ['', '', '', '', '']);

            $rows[] = $rowTotP;
            $rows[] = $rowTotA;
            $rows[] = $rowTotJ;
            $rows[] = $rowM;
            $rows[] = $rowF;

            $content = SimpleXlsxGenerator::generate([], $rows, 'Reporte', $merges);

            return response()->json([
                'success' => true,
                'data' => [
                    'filename' => 'Reporte_Semanal_Grupo_Alumno.xlsx',
                    'content' => base64_encode($content)
                ],
                'message' => 'Exportación Excel generada'
            ]);
        }

        if ($format === 'pdf') {
            $viewData = $this->prepareGrupoAlumnoDataForPdf($data, $fechaInicio, $fechaFin, $grupoId);
            $logoBase64 = ''; // Puedes cargar un logo si es necesario
            $institucion = config('app.nombre_institucion', 'Institución Educativa');
            $viewData['logoBase64'] = $logoBase64;
            $viewData['institucion'] = $institucion;

            $html = $this->generateGrupoAlumnoPdfHtml($viewData);

            $pdf = SnappyPdf::loadHTML($html)
                ->setPaper('legal')
                ->setOrientation('landscape')
                ->setOption('margin-top', 10)
                ->setOption('margin-right', 10)
                ->setOption('margin-bottom', 10)
                ->setOption('margin-left', 10)
                ->setOption('encoding', 'utf-8');

            $content = $pdf->output();

            return response()->json([
                'success' => true,
                'data' => [
                    'filename' => 'Reporte_Semanal_Grupo_Alumno.pdf',
                    'content' => base64_encode($content)
                ],
                'message' => 'Exportación PDF generada'
            ]);
        }

        return $this->errorResponse('Formato no soportado', [], 400);
    }

    public function reporteSemanalPorGrupo(Request $request): JsonResponse
    {
        $fechaInicio = (string)$request->query('fecha_inicio');
        $fechaFin = (string)$request->query('fecha_fin');
        $format = strtolower((string)$request->query('export', 'json'));

        $data = $this->service->reporteSemanalPorGrupo($fechaInicio, $fechaFin);

        if ($format === 'json') {
            return $this->successResponse($data, 'Reporte semanal por grupo');
        }

        if ($format === 'xlsx') {
            $rows = [];
            foreach ($data['semanas'] as $sem) {
                // Header of week
                $rows[] = ['REPORTE DE ASISTENCIA DIARIA DEL NIVEL SECUNDARIA - ' . $sem['etiqueta']];
                $headings = ['I:E: "SAN JOSE DE TARBES" - PIURA', 'TOTAL EST.'];
                $subHeadings = ['GRADO DE ESTUDIOS', 'MATRI.'];
                $merges = [];

                $colIndex = 3;
                foreach ($sem['dias'] as $i => $d) {
                    $date = \Carbon\Carbon::parse($d);
                    $diaLetra = ['1' => 'L', '2' => 'K', '3' => 'M', '4' => 'J', '5' => 'V', '6' => 'S', '0' => 'D'][$date->format('w')];
                    $headings[] = $diaLetra . "\n" . $date->format('d');
                    $subHeadings[] = 'ASIST.';
                    $colIndex++;
                }

                $headings[] = '%';
                $subHeadings[] = 'ASIST';

                $rows[] = $headings;
                $rows[] = $subHeadings;

                $totalMatricula = 0;
                $totalesCol = array_fill(0, count($sem['dias']), 0);
                $sumaPorcentajesSemanal = 0;
                $grpsCount = 0;

                foreach ($sem['detalle_grupos'] as $grp) {
                    $r = [$grp['grupo'], $grp['matricula']];
                    $totalMatricula += $grp['matricula'];
                    $j = 0;
                    foreach ($sem['dias'] as $d) {
                        $val = $grp['asistencia_por_dia'][$d];
                        $r[] = $val;
                        if (is_numeric($val)) {
                            $totalesCol[$j] += $val;
                        }
                        $j++;
                    }
                    $r[] = $grp['porcentaje_sesion'];
                    $rows[] = $r;
                    $grpsCount++;
                }

                // Row totals
                $tRow = ['TOTALES ASISTENCIA DEL NIVEL', $totalMatricula];
                foreach ($totalesCol as $t) {
                    $tRow[] = $t;
                }
                $tRow[] = $sem['porcentaje_asist_semanal'];
                $rows[] = $tRow;

                // Row %
                $pRow = ['PORCENTAJE ASISTENCIA GENERAL', '100%'];
                $j = 0;
                foreach ($sem['dias'] as $d) {
                    $pRow[] = $sem['porcentaje_de_la_semana'][$d] ?? '0%';
                    $j++;
                }
                $pRow[] = '';
                $rows[] = $pRow;

                $rows[] = []; // empty row separator
            }

            $content = SimpleXlsxGenerator::generate([], $rows, 'Reporte');

            return response()->json([
                'success' => true,
                'data' => [
                    'filename' => 'Reporte_Asistencia_Diaria_Evolucion.xlsx',
                    'content' => base64_encode($content)
                ],
                'message' => 'Exportación Excel generada'
            ]);
        }

        if ($format === 'pdf') {
            $viewData = $this->prepareGrupoSemanalDataForPdf($data);
            $html = $this->generateGrupoSemanalPdfHtml($viewData);

            $pdf = SnappyPdf::loadHTML($html)
                ->setPaper('a4')
                ->setOrientation('portrait')
                ->setOption('margin-top', 10)
                ->setOption('margin-right', 10)
                ->setOption('margin-bottom', 10)
                ->setOption('margin-left', 10)
                ->setOption('encoding', 'utf-8');

            $content = $pdf->output();

            return response()->json([
                'success' => true,
                'data' => [
                    'filename' => 'Reporte_Asistencia_Diaria_Evolucion.pdf',
                    'content' => base64_encode($content)
                ],
                'message' => 'Exportación PDF generada'
            ]);
        }

        return $this->errorResponse('Formato no soportado', [], 400);
    }

    public function reporteInasistenciasPorGrupo(Request $request): JsonResponse
    {
        $grupoId = (int)$request->query('grupo_id', 0);
        $fechaInicio = (string)$request->query('fecha_inicio');
        $fechaFin = (string)$request->query('fecha_fin');
        $periodoLectivoId = (int)$request->query('periodo_lectivo_id', 0);
        $format = strtolower((string)$request->query('export', 'json'));

        try {
            $data = $this->service->reporteInasistenciasPorGrupo($grupoId, $fechaInicio, $fechaFin, $periodoLectivoId > 0 ? $periodoLectivoId : null);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), [], 422);
        }

        if ($format === 'json') {
            return $this->successResponse($data, 'Reporte de inasistencias por grupo');
        }

        if ($format === 'xlsx') {
            $rows = [];
            foreach ($data['semanas'] as $sem) {
                $rows[] = ['REPORTE ESTUDIANTES CON INASISTENCIAS'];
                $rows[] = ['FECHA: ' . $sem['etiqueta']];
                $rows[] = [];

                // Generar los headers para esta semana
                $headings = ['GRADOS'];
                foreach ($sem['fechas'] as $f) {
                    $headings[] = $f['dia_nombre'] . " " . $f['dia_mes'];
                    $headings[] = ''; // Espacio para el código del estudiante al lado de su nombre si quisiéramos
                }
                $rows[] = $headings;

                foreach ($sem['detalle_grupos'] as $grp) {
                    // Contar cuántos estudiantes máximo hay en un día de esta semana para este grupo (para saber cuántas filas hacer)
                    $maxFilasEstudiantes = 0;
                    foreach ($sem['fechas'] as $f) {
                        $fechaFull = $f['fecha'];
                        if (isset($grp['dias'][$fechaFull])) {
                            $contador = count($grp['dias'][$fechaFull]);
                            if ($contador > $maxFilasEstudiantes) {
                                $maxFilasEstudiantes = $contador;
                            }
                        }
                    }

                    if ($maxFilasEstudiantes === 0) {
                        $ro = [$grp['grupo']];
                        foreach ($sem['fechas'] as $f) {
                            $ro[] = '';
                            $ro[] = '';
                        }
                        $rows[] = $ro;
                    } else {
                        // Generar las filas de estudiantes
                        for ($i = 0; $i < $maxFilasEstudiantes; $i++) {
                            $ro = [];
                            if ($i === 0) {
                                $ro[] = $grp['grupo']; // Poner el nombre del grupo solo en la primera fila
                            } else {
                                $ro[] = '';
                            }

                            foreach ($sem['fechas'] as $f) {
                                $fechaFull = $f['fecha'];
                                if (isset($grp['dias'][$fechaFull]) && isset($grp['dias'][$fechaFull][$i])) {
                                    $est = $grp['dias'][$fechaFull][$i];
                                    $ro[] = $est['nombre'];
                                    $ro[] = $est['codigo'];
                                } else {
                                    $ro[] = '';
                                    $ro[] = '';
                                }
                            }
                            $rows[] = $ro;
                        }
                    }

                    // Calcular el consolidado del grupo específicamente para esta tabla
                    $consolidadoGrupo = [];
                    foreach ($sem['fechas'] as $f) {
                        $fechaFull = $f['fecha'];
                        $consolidadoGrupo[$fechaFull] = ['INJ' => 0, 'JUS' => 0, 'PER' => 0, 'SUS' => 0];
                        if (isset($grp['dias'][$fechaFull])) {
                            foreach ($grp['dias'][$fechaFull] as $est) {
                                if ($est['codigo'] === 'AI' || $est['codigo'] === 'T') $consolidadoGrupo[$fechaFull]['INJ']++;
                                elseif ($est['codigo'] === 'AJ') $consolidadoGrupo[$fechaFull]['JUS']++;
                                elseif ($est['codigo'] === 'PER') $consolidadoGrupo[$fechaFull]['PER']++;
                                elseif ($est['codigo'] === 'SUS') $consolidadoGrupo[$fechaFull]['SUS']++;
                            }
                        }
                    }

                    $estados = [
                        ['PERMISO', 'PER'],
                        ['INASISTENCIAS', 'INJ'],
                        ['JUSTIFICADOS', 'JUS'],
                        ['SUSPENDIDOS', 'SUS']
                    ];

                    foreach ($estados as $estArr) {
                        $r = [$estArr[0]];
                        foreach ($sem['fechas'] as $f) {
                            $fechaFull = $f['fecha'];
                            $r[] = 'TOTAL';
                            $r[] = $consolidadoGrupo[$fechaFull][$estArr[1]];
                        }
                        $rows[] = $r;
                    }

                    // TOTAL GLOBAL (suma de los estados)
                    $rTot = ['TOTAL'];
                    foreach ($sem['fechas'] as $f) {
                        $fechaFull = $f['fecha'];
                        $suma = $consolidadoGrupo[$fechaFull]['INJ'] + $consolidadoGrupo[$fechaFull]['JUS'] + $consolidadoGrupo[$fechaFull]['PER'] + $consolidadoGrupo[$fechaFull]['SUS'];
                        $rTot[] = 'TOTAL';
                        $rTot[] = $suma;
                    }
                    $rows[] = $rTot;
                    $rows[] = []; // Espaciador entre grupos
                }
            }

            $content = SimpleXlsxGenerator::generate([], $rows, 'Reporte');

            return response()->json([
                'success' => true,
                'data' => [
                    'filename' => 'Reporte_Inasistencias.xlsx',
                    'content' => base64_encode($content)
                ],
                'message' => 'Exportación Excel generada'
            ]);
        }

        if ($format === 'pdf') {
            $viewData = $this->prepareInasistenciasDataForPdf($data);
            $html = $this->generateInasistenciasPdfHtml($viewData);

            $pdf = SnappyPdf::loadHTML($html)
                ->setPaper('a4')
                ->setOrientation('portrait')
                ->setOption('margin-top', 10)
                ->setOption('margin-right', 10)
                ->setOption('margin-bottom', 10)
                ->setOption('margin-left', 10)
                ->setOption('encoding', 'utf-8');

            $content = $pdf->output();

            return response()->json([
                'success' => true,
                'data' => [
                    'filename' => 'Reporte_Inasistencias.pdf',
                    'content' => base64_encode($content)
                ],
                'message' => 'Exportación PDF generada'
            ]);
        }

        return $this->errorResponse('Formato no soportado', [], 400);
    }

    public function reporteGlobalPorRangoFechas(Request $request): JsonResponse
    {
        $fechaInicio = (string)$request->query('fecha_inicio');
        $fechaFin = (string)$request->query('fecha_fin');
        $periodoLectivoId = (int)$request->query('periodo_lectivo_id', 0);

        if (!$periodoLectivoId) {
            $periodo = \App\Models\ConfPeriodoLectivo::where('periodo_nota', true)->first();
            if ($periodo) {
                $periodoLectivoId = $periodo->id;
            }
        }

        if (!$periodoLectivoId) {
            return $this->errorResponse('Periodo lectivo no proporcionado', [], 422);
        }

        $data = $this->service->reporteGlobalPorRangoFechas($periodoLectivoId, $fechaInicio, $fechaFin);
        return $this->successResponse($data, 'Reporte global por rango');
    }

    public function exportReporteGlobalPorRangoFechas(Request $request): JsonResponse
    {
        return $this->errorResponse('Not implemented', [], 501);
    }

    public function reporteConsolidadoAsistenciaMatricula(Request $request): JsonResponse
    {
        return $this->errorResponse('Not implemented here, functionality merged in other reports', [], 501);
    }

    public function reportePorcentajeMatricula(Request $request): JsonResponse
    {
        return $this->errorResponse('Not implemented here, functionality merged in other reports', [], 501);
    }

    // --- Funciones Helper para Exportación PDF ---

    private function prepareGrupoAlumnoDataForPdf(array $data, string $fechaInicio, string $fechaFin, int $grupoId): array
    {
        $semanas = [];
        foreach ($data['fechas'] as $f) {
            $date = \Carbon\Carbon::parse($f);
            $key = $date->format('o-W');

            if (!isset($semanas[$key])) {
                $sw = $date->copy()->startOfWeek();
                $ew = $date->copy()->endOfWeek();
                $semanas[$key] = [
                    'etiqueta' => 'DEL ' . $sw->format('d/m') . ' AL ' . $ew->format('d/m'),
                    'dias' => []
                ];
            }
            $d = ['1' => 'L', '2' => 'K', '3' => 'M', '4' => 'J', '5' => 'V', '6' => 'S', '0' => 'D'][$date->format('w')];
            $semanas[$key]['dias'][] = [
                'fecha' => $f,
                'dia' => $d,
                'dia_mes' => $date->format('d')
            ];
        }

        return [
            'grupo' => $data['grupo'],
            'fechaInicio' => \Carbon\Carbon::parse($fechaInicio)->format('d-m-Y'),
            'fechaFin' => \Carbon\Carbon::parse($fechaFin)->format('d-m-Y'),
            'semanas' => $semanas,
            'fechas' => $data['fechas'],
            'alumnos' => $data['alumnos'],
            'totales_por_dia' => $data['totales_por_dia']
        ];
    }

    private function generateGrupoAlumnoPdfHtml(array $viewData): string
    {
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8">';
        $html .= '<style>';
        $html .= 'body { font-family: Helvetica, Arial, sans-serif; font-size: 10px; }';
        $html .= 'table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }';
        $html .= 'th, td { border: 1px solid #000; padding: 3px; text-align: center; }';
        $html .= 'th { background-color: #f2f2f2; font-weight: bold; }';
        $html .= '.text-left { text-align: left; }';
        $html .= '.header-table { border: none; }';
        $html .= '.header-table th, .header-table td { border: none; padding: 2px; }';
        $html .= '.title { font-size: 14px; font-weight: bold; text-align: center; margin: 10px 0; }';
        $html .= '.bullet { font-size: 14px; line-height: 10px; }';
        $html .= '</style></head><body>';

        // Header
        $html .= '<table class="header-table" style="width: 100%; margin-bottom: 5px;">
            <tr>
                <td style="width: 15%; text-align: left;"><img src="' . $viewData['logoBase64'] . '" alt="Logo" style="height: 50px;" /></td>
                <td style="width: 70%; text-align: center;">
                    <strong>' . $viewData['institucion'] . '</strong><br/>
                    Sistema de Gestión de Asistencias
                </td>
                <td style="width: 15%; text-align: right;"></td>
            </tr>
        </table>';

        $html .= '<div class="title">REPORTE SEMANAL POR GRUPO Y ALUMNO</div>';
        $html .= '<div style="margin-bottom: 10px;">';
        $html .= '<strong>Grado y Sección:</strong> ' . $viewData['grupo'] . '&nbsp;&nbsp;&nbsp;&nbsp;';
        $html .= '<strong>Periodo:</strong> ' . $viewData['fechaInicio'] . ' al ' . $viewData['fechaFin'];
        $html .= '</div>';

        $html .= '<table><thead>';
        $html .= '<tr>';
        $html .= '<th rowspan="2" style="width: 2%;">N°</th>';
        $html .= '<th rowspan="2" style="width: 25%;" class="text-left">ALUMNOS(AS)</th>';

        foreach ($viewData['semanas'] as $sem) {
            $html .= '<th colspan="' . count($sem['dias']) . '">' . $sem['etiqueta'] . '</th>';
        }

        $html .= '<th colspan="5">RESUMEN</th>';
        $html .= '</tr><tr>';

        foreach ($viewData['semanas'] as $sem) {
            foreach ($sem['dias'] as $d) {
                $html .= '<th>' . $d['dia'] . '<br/>' . $d['dia_mes'] . '</th>';
            }
        }

        $html .= '<th><span title="Días Asistidos">H.A</span></th>';
        $html .= '<th><span title="Días Inasistidos">H.I</span></th>';
        $html .= '<th><span title="Días Justificados">H.J</span></th>';
        $html .= '<th><span title="Días Tardanzas">H.T</span></th>';
        $html .= '<th><span title="Permisos y Suspendidos">Per.</span></th>';
        $html .= '</tr></thead><tbody>';

        $count = 1;
        foreach ($viewData['alumnos'] as $al) {
            $html .= '<tr>';
            $html .= '<td>' . $count++ . '</td>';
            $html .= '<td class="text-left">' . htmlspecialchars($al['nombre']) . '</td>';

            foreach ($viewData['fechas'] as $f) {
                $val = $al['dias'][$f] ?? '-';
                if ($val === 'p') $val = '<span class="bullet">•</span>';
                elseif ($val === 'A') $val = '<span style="color:red">F</span>';
                elseif ($val === 'J') $val = '<span style="color:blue">J</span>';
                elseif ($val === 'T') $val = '<span style="color:orange">T</span>';
                elseif ($val === 'Permiso') $val = 'P';
                elseif ($val === 'Suspendido') $val = 'S';
                $html .= '<td>' . $val . '</td>';
            }

            $html .= '<td>' . $al['totales']['presentes'] . '</td>';
            $html .= '<td>' . $al['totales']['ausentes'] . '</td>';
            $html .= '<td>' . $al['totales']['justificados'] . '</td>';
            $html .= '<td>' . $al['totales']['tardanzas'] . '</td>';
            $html .= '<td>' . ($al['totales']['permisos'] + $al['totales']['suspendidos']) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody><tfoot>';

        $footerRows = [
            ['label' => 'T. de Estudiantes Presentes', 'key' => 'presentes'],
            ['label' => 'T. de Estudiantes Ausentes', 'key' => 'ausentes'],
            ['label' => 'T. de Estudiantes Justificados', 'key' => 'justificados'],
            ['label' => 'Varones Presentes', 'key' => 'm_presentes'],
            ['label' => 'Mujeres Presentes', 'key' => 'f_presentes'],
        ];

        foreach ($footerRows as $rowDesc) {
            $html .= '<tr>';
            $html .= '<td colspan="2" class="text-left" style="font-weight:bold">' . $rowDesc['label'] . '</td>';
            foreach ($viewData['fechas'] as $f) {
                $t = $viewData['totales_por_dia'][$f] ?? null;
                $val = ($t && $t[$rowDesc['key']] > 0) ? $t[$rowDesc['key']] : '';
                $html .= '<td>' . $val . '</td>';
            }
            $html .= '<td colspan="5"></td>';
            $html .= '</tr>';
        }

        $html .= '</tfoot></table>';
        $html .= '</body></html>';

        return $html;
    }

    private function prepareGrupoSemanalDataForPdf(array $data): array
    {
        return $data; // The structure is already suitable
    }

    private function generateGrupoSemanalPdfHtml(array $viewData): string
    {
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8">';
        $html .= '<style>';
        $html .= 'body { font-family: Helvetica, Arial, sans-serif; font-size: 10px; }';
        $html .= 'table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }';
        $html .= 'th, td { border: 1px solid #000; padding: 4px; text-align: center; }';
        $html .= 'th { background-color: #f2f2f2; font-weight: bold; }';
        $html .= '.text-left { text-align: left; }';
        $html .= '.title { font-size: 14px; font-weight: bold; text-align: center; margin: 10px 0; background-color: #000; color: #fff; padding: 5px; }';
        $html .= '.label-row { background-color: #d9edf7; font-weight: bold; }';
        $html .= '</style></head><body>';

        foreach ($viewData['semanas'] as $sem) {
            $html .= '<div class="title">REPORTE DE ASISTENCIA DIARIA DEL NIVEL SECUNDARIA - ' . $sem['etiqueta'] . '</div>';

            $html .= '<table><thead>';
            $html .= '<tr>';
            $html .= '<th colspan="2">I.E. "SAN JOSE DE TARBES" - PIURA</th>';
            $html .= '<th colspan="' . count($sem['dias']) . '">TOTAL EST.</th>';
            $html .= '<th rowspan="2">%<br/>ASIST</th>';
            $html .= '</tr><tr>';
            $html .= '<th class="text-left" style="width:30%">GRADO DE ESTUDIOS</th>';
            $html .= '<th style="width:10%">MATRI.</th>';

            foreach ($sem['dias'] as $d) {
                $date = \Carbon\Carbon::parse($d);
                $diaLetra = ['1' => 'L', '2' => 'K', '3' => 'M', '4' => 'J', '5' => 'V', '6' => 'S', '0' => 'D'][$date->format('w')];
                $html .= '<th>' . $diaLetra . '<br/>' . $date->format('d') . '</th>';
            }

            $html .= '</tr></thead><tbody>';

            $totalMatricula = 0;
            $totalesCol = array_fill(0, count($sem['dias']), 0);

            foreach ($sem['detalle_grupos'] as $grp) {
                $html .= '<tr>';
                $html .= '<td class="text-left">' . $grp['grupo'] . '</td>';
                $html .= '<td>' . $grp['matricula'] . '</td>';
                $totalMatricula += $grp['matricula'];

                $j = 0;
                foreach ($sem['dias'] as $d) {
                    $val = $grp['asistencia_por_dia'][$d];
                    $html .= '<td>' . $val . '</td>';
                    if (is_numeric($val)) {
                        $totalesCol[$j] += $val;
                    }
                    $j++;
                }

                $html .= '<td>' . $grp['porcentaje_sesion'] . '</td>';
                $html .= '</tr>';
            }

            // Totales Row
            $html .= '<tr class="label-row">';
            $html .= '<td class="text-left">TOTALES ASISTENCIA DEL NIVEL</td>';
            $html .= '<td>' . $totalMatricula . '</td>';
            foreach ($totalesCol as $t) {
                $html .= '<td>' . $t . '</td>';
            }
            $html .= '<td>' . $sem['porcentaje_asist_semanal'] . '</td>';
            $html .= '</tr>';

            // Porcentaje Row
            $html .= '<tr class="label-row">';
            $html .= '<td class="text-left">PORCENTAJE ASISTENCIA GENERAL</td>';
            $html .= '<td>100%</td>';
            foreach ($sem['dias'] as $d) {
                $html .= '<td>' . ($sem['porcentaje_de_la_semana'][$d] ?? '0%') . '</td>';
            }
            $html .= '<td></td>';
            $html .= '</tr>';

            $html .= '</tbody></table>';
            $html .= '<div style="page-break-after: always;"></div>';
        }

        $html .= '</body></html>';
        return $html;
    }

    private function prepareInasistenciasDataForPdf(array $data): array
    {
        return $data;
    }

    private function generateInasistenciasPdfHtml(array $viewData): string
    {
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8">';
        $html .= '<style>';
        $html .= 'body { font-family: Helvetica, Arial, sans-serif; font-size: 10px; }';
        $html .= 'table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }';
        $html .= 'th, td { border: 1px solid #000; padding: 4px; text-align: center; }';
        $html .= 'th { background-color: #f2f2f2; font-weight: bold; }';
        $html .= '.text-left { text-align: left; }';
        $html .= '.title { font-size: 14px; font-weight: bold; text-align: center; margin: 10px 0; }';
        $html .= '.subtitle { font-size: 12px; font-weight: bold; margin-bottom: 5px; }';
        $html .= '.resumen-table { width: 50%; margin: 0 auto; }';
        $html .= '</style></head><body>';

        foreach ($viewData['semanas'] as $sem) {
            foreach ($sem['detalle_grupos'] as $grp) {
                $html .= '<div class="title">REPORTE ESTUDIANTES CON INASISTENCIAS</div>';
                $html .= '<div class="subtitle">FECHA: ' . $sem['etiqueta'] . '</div>';
                $html .= '<div class="subtitle">Grado / Sección: ' . $grp['grupo'] . '</div>';

                $html .= '<table><thead><tr>';
                $html .= '<th style="width: 20%; background-color:#92D050;">GRADOS</th>';
                foreach ($sem['fechas'] as $f) {
                    $html .= '<th style="background-color:#92D050;" colspan="2">' . $f['dia_nombre'] . '<br/>' . $f['dia_mes'] . '</th>';
                }
                $html .= '</tr></thead><tbody>';

                // Calcular el número máximo de filas necesarias para este grupo
                $maxFilasEstudiantes = 0;
                foreach ($sem['fechas'] as $f) {
                    $fechaFull = $f['fecha'];
                    if (isset($grp['dias'][$fechaFull])) {
                        $contador = count($grp['dias'][$fechaFull]);
                        if ($contador > $maxFilasEstudiantes) {
                            $maxFilasEstudiantes = $contador;
                        }
                    }
                }

                if ($maxFilasEstudiantes === 0) {
                    $html .= '<tr>';
                    $html .= '<td style="background-color:#FFFF00; font-weight:bold;">' . htmlspecialchars($grp['grupo']) . '</td>';
                    foreach ($sem['fechas'] as $f) {
                        $html .= '<td colspan="2"></td>';
                    }
                    $html .= '</tr>';
                } else {
                    for ($i = 0; $i < $maxFilasEstudiantes; $i++) {
                        $html .= '<tr>';
                        if ($i === 0) {
                            $html .= '<td rowspan="' . $maxFilasEstudiantes . '" style="background-color:#FFFF00; font-weight:bold; vertical-align:middle;">' . htmlspecialchars($grp['grupo']) . '</td>';
                        }

                        foreach ($sem['fechas'] as $f) {
                            $fechaFull = $f['fecha'];
                            if (isset($grp['dias'][$fechaFull]) && isset($grp['dias'][$fechaFull][$i])) {
                                $est = $grp['dias'][$fechaFull][$i];
                                $html .= '<td class="text-left" style="border-right:none; font-size:9px;">' . htmlspecialchars($est['nombre']) . '</td>';
                                $html .= '<td style="border-left:none; font-weight:bold;">' . htmlspecialchars($est['codigo']) . '</td>';
                            } else {
                                $html .= '<td colspan="2"></td>';
                            }
                        }
                        $html .= '</tr>';
                    }
                }

                // Calcular el consolidado del grupo
                $consolidadoGrupo = [];
                foreach ($sem['fechas'] as $f) {
                    $fechaFull = $f['fecha'];
                    $consolidadoGrupo[$fechaFull] = ['INJ' => 0, 'JUS' => 0, 'PER' => 0, 'SUS' => 0];
                    if (isset($grp['dias'][$fechaFull])) {
                        foreach ($grp['dias'][$fechaFull] as $est) {
                            if ($est['codigo'] === 'AI' || $est['codigo'] === 'T') $consolidadoGrupo[$fechaFull]['INJ']++;
                            elseif ($est['codigo'] === 'AJ') $consolidadoGrupo[$fechaFull]['JUS']++;
                            elseif ($est['codigo'] === 'PER') $consolidadoGrupo[$fechaFull]['PER']++;
                            elseif ($est['codigo'] === 'SUS') $consolidadoGrupo[$fechaFull]['SUS']++;
                        }
                    }
                }

                $estados = [
                    ['label' => 'PERMISO', 'key' => 'PER', 'bg' => '#BDD7EE', 'color' => '#000'],
                    ['label' => 'INASISTENCIAS', 'key' => 'INJ', 'bg' => '#FFFF00', 'color' => '#000'],
                    ['label' => 'JUSTIFICADOS', 'key' => 'JUS', 'bg' => '#92D050', 'color' => '#000'],
                    ['label' => 'SUSPENDIDOS', 'key' => 'SUS', 'bg' => '#FF0000', 'color' => '#FFF']
                ];

                foreach ($estados as $estMap) {
                    $html .= '<tr>';
                    $html .= '<td style="font-weight:bold; background-color:' . $estMap['bg'] . '; color:' . $estMap['color'] . ';">' . $estMap['label'] . '</td>';
                    foreach ($sem['fechas'] as $f) {
                        $fechaFull = $f['fecha'];
                        $val = $consolidadoGrupo[$fechaFull][$estMap['key']];
                        $html .= '<td style="background-color:#F8CBAD; font-weight:bold;">TOTAL</td>';
                        $html .= '<td style="font-weight:bold; background-color:' . $estMap['bg'] . '; color:' . $estMap['color'] . ';">' . $val . '</td>';
                    }
                    $html .= '</tr>';
                }

                // Fila Total Global
                $html .= '<tr>';
                $html .= '<td style="font-weight:bold; background-color:#548235; color:#FFF;">TOTAL</td>';
                foreach ($sem['fechas'] as $f) {
                    $fechaFull = $f['fecha'];
                    $suma = $consolidadoGrupo[$fechaFull]['INJ'] + $consolidadoGrupo[$fechaFull]['JUS'] + $consolidadoGrupo[$fechaFull]['PER'] + $consolidadoGrupo[$fechaFull]['SUS'];
                    $html .= '<td style="background-color:#548235; color:#FFF; font-weight:bold;">TOTAL</td>';
                    $html .= '<td style="background-color:#548235; color:#FFF; font-weight:bold;">' . $suma . '</td>';
                }
                $html .= '</tr>';

                $html .= '</tbody></table>';
                $html .= '<div style="page-break-after: always;"></div>';
            }
        }

        $html .= '</body></html>';
        return $html;
    }

    private function getColLetter(int $num): string
    {
        $numeric = ($num - 1) % 26;
        $letter = chr(65 + $numeric);
        $num2 = intval(($num - 1) / 26);
        if ($num2 > 0) {
            return $this->getColLetter($num2) . $letter;
        } else {
            return $letter;
        }
    }
}
