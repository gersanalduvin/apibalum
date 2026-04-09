<?php

namespace App\Services;

use App\Repositories\NotAsignaturaGradoRepository;
use App\Models\ConfPeriodoLectivo;
use App\Models\ConfigGrado;
use App\Models\ConfigNotEscala;
use App\Models\NotMateria;
use App\Models\NotAsignaturaGrado;
use App\Models\ConfigNotSemestreParcial;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Barryvdh\Snappy\Facades\SnappyPdf;
use App\Utils\SimpleXlsxGenerator;

class NotAsignaturaGradoService
{
    public function __construct(private NotAsignaturaGradoRepository $repository) {}

    public function getAsignaturasPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getPaginatedWithRelations($filters, $perPage);
    }

    public function upsertAsignaturaAndRelations(array $data)
    {
        return DB::transaction(function () use ($data) {
            $cortes = $data['cortes'] ?? [];
            $parametros = $data['parametros'] ?? [];
            $hijas = $data['hijas'] ?? [];
            unset($data['cortes'], $data['parametros'], $data['hijas']);

            if (!empty($data['id'])) {
                $asig = $this->repository->update((int) $data['id'], $data);
            } else {
                $asig = $this->repository->create($data);
            }

            $this->repository->upsertCortes($asig->id, $cortes);
            $this->repository->upsertParametros($asig->id, $parametros);
            $this->repository->syncHijas($asig->id, $hijas);

            return $this->repository->find($asig->id);
        });
    }

    public function reorder(array $orders): bool
    {
        return DB::transaction(function () use ($orders) {
            foreach ($orders as $item) {
                NotAsignaturaGrado::where('id', $item['id'])
                    ->update(['orden' => $item['orden']]);
            }
            return true;
        });
    }

    public function deleteAsignatura(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            return $this->repository->delete($id);
        });
    }

    public function deleteCorte(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            return $this->repository->deleteCorte($id);
        });
    }

    public function getPeriodosLectivosYGrados(?int $periodoLectivoId = null): array
    {
        $periodos = ConfPeriodoLectivo::orderBy('nombre')->get();
        $grados = ConfigGrado::orderBy('orden')->orderBy('nombre')->get();
        $escalas = ConfigNotEscala::orderBy('nombre')->get();
        $materias = NotMateria::orderBy('nombre')->get();
        return [
            'periodos' => $periodos,
            'grados' => $grados,
            'escalas' => $escalas,
            'materias' => $materias,
        ];
    }

    public function exportPdf(array $filters = [])
    {
        $rows = $this->repository->getPaginatedWithRelations($filters, 1000);
        $data = $rows->items();

        $periodoId = $filters['periodo_lectivo_id'] ?? null;
        $periodo = $periodoId ? \App\Models\ConfPeriodoLectivo::find($periodoId) : null;

        $html = view('pdf.not-asignatura-grado', [
            'items' => $data,
            'filters' => $filters,
        ])->render();

        $titulo = 'CONFIGURACIÓN - ASIGNATURAS POR GRADO';
        $subtitulo1 = 'Periodo: ' . ($periodo->nombre ?? ($periodoId ?? 'N/A'));
        $subtitulo2 = 'Fecha de generación: ' . now()->format('d/m/Y H:i');

        $nombreInstitucion = config('app.nombre_institucion', 'MINISTERIO DE EDUCACIÓN');
        $headerHtml = view()->make('pdf.header', compact('titulo', 'subtitulo1', 'subtitulo2', 'nombreInstitucion'))->render();

        $pdf = SnappyPdf::loadHTML($html)
            ->setPaper('letter')
            ->setOrientation('portrait')
            ->setOption('margin-top', 35)
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 20)
            ->setOption('margin-left', 10)
            ->setOption('encoding', 'utf-8')
            ->setOption('enable-local-file-access', true)
            ->setOption('header-html', $headerHtml)
            ->setOption('header-spacing', 5)
            ->setOption('footer-left', 'Fecha y hora: [date] [time]')
            ->setOption('footer-right', 'Página [page] de [toPage]')
            ->setOption('footer-spacing', 5)
            ->setOption('load-error-handling', 'ignore');

        $nombreArchivo = 'config_asignaturas_grado_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        return $pdf->download($nombreArchivo);
    }

    public function exportExcel(array $filters = [])
    {
        $rows = $this->repository->getPaginatedWithRelations($filters, 1000);
        $data = $rows->items();

        $headings = ['Periodo', 'Grado', 'Materia', 'Escala', 'Nota aprobar', 'Nota máxima', 'Incluye promedio', 'Incluye MINED', 'Orden', 'Tipo evaluación', 'Educación iniciativa', 'Corte', 'Evidencia'];
        $excelRows = [];
        foreach ($data as $it) {
            $base = [
                optional($it->periodoLectivo)->nombre ?? '',
                optional($it->grado)->nombre ?? '',
                optional($it->materia)->nombre ?? '',
                optional($it->escala)->nombre ?? '',
                (string) ($it->nota_aprobar ?? 0),
                (string) ($it->nota_maxima ?? 0),
                $it->incluir_en_promedio ? 'Sí' : 'No',
                $it->incluir_en_reporte_mined ? 'Sí' : 'No',
                (string) ($it->orden ?? 0),
                $it->tipo_evaluacion ?? '',
                $it->es_para_educacion_iniciativa ? 'Sí' : 'No',
            ];
            if (count($it->cortes)) {
                foreach ($it->cortes as $c) {
                    if (count($c->evidencias)) {
                        foreach ($c->evidencias as $ev) {
                            $excelRows[] = array_merge($base, [optional($c->corte)->nombre ?? '', $ev->evidencia ?? '']);
                        }
                    } else {
                        $excelRows[] = array_merge($base, [optional($c->corte)->nombre ?? '', '']);
                    }
                }
            } else {
                $excelRows[] = array_merge($base, ['', '']);
            }
        }

        $metaRows = [
            ['Reporte', 'Configuración - Asignaturas por Grado'],
            ['Generado', now()->format('Y-m-d H:i')],
            [],
        ];
        $metaRows = array_values(array_filter($metaRows, fn($r) => !empty($r)));

        $binary = SimpleXlsxGenerator::generateWithMeta($metaRows, $headings, $excelRows);
        return response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="configuracion_asignaturas_grado.xlsx"'
        ]);
    }

    public function getAlternativasYParciales(int $periodoLectivoId, int $gradoId, ?int $excludeAsignaturaId = null, ?int $grupoId = null): array
    {
        $asignaturasQuery = NotAsignaturaGrado::where('periodo_lectivo_id', $periodoLectivoId)
            ->where('grado_id', $gradoId)
            ->where('incluir_boletin', 1);

        if (!empty($excludeAsignaturaId)) {
            $asignaturasQuery->where('id', '!=', $excludeAsignaturaId);
        }

        // --- Teacher Logic ---
        $user = \Illuminate\Support\Facades\Auth::user();
        $isDocente = $user && $user->tipo_usuario === 'docente'; // Assuming 'docente' is the exact string or constant
        // Or check role/permission if better. 'docente' is safe if consistent.

        if ($isDocente && $grupoId) {
            // Check if Guide Teacher
            $group = \App\Models\ConfigGrupos::find($grupoId);
            $isGuide = $group && $group->docente_guia === $user->id;

            if (!$isGuide) {
                // Filter only assigned subjects for this group
                // Structure: not_asignatura_grado_docente (grupo_id, asignatura_grado_id, user_id)
                $assignedAsignaturaIds = \App\Models\NotAsignaturaGradoDocente::where('grupo_id', $grupoId)
                    ->where('user_id', $user->id)
                    ->pluck('asignatura_grado_id')
                    ->toArray();

                $asignaturasQuery->whereIn('id', $assignedAsignaturaIds);
            }
        }
        // ---------------------

        $asignaturas = $asignaturasQuery
            ->with(['materia', 'cortes'])
            ->orderBy('orden')
            ->orderBy('id')
            ->get()
            ->map(function ($it) {
                return [
                    'id' => $it->id,
                    'materia' => optional($it->materia)->nombre ?? '',
                    'cortes_ids' => $it->cortes->pluck('corte_id')->toArray()
                ];
            });

        $parciales = ConfigNotSemestreParcial::with('semestre')
            ->whereHas('semestre', function ($q) use ($periodoLectivoId) {
                $q->where('periodo_lectivo_id', $periodoLectivoId);
            })
            ->orderBy('semestre_id')->orderBy('orden')->get();

        return [
            'asignaturas' => $asignaturas,
            'parciales' => $parciales,
        ];
    }

    public function getById(int $id)
    {
        return $this->repository->find($id);
    }

    public function updateConfigValues(int $id, array $data): bool
    {
        return $this->repository->update($id, $data) ? true : false;
    }
}
