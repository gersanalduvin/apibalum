<?php

namespace App\Services;

use App\Repositories\AsignaturaGradoDocenteRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

class AsignaturaGradoDocenteService
{
    public function __construct(private AsignaturaGradoDocenteRepository $repository) {}

    public function getById(int $id)
    {
        return $this->repository->find($id);
    }

    public function getByDocente(int $userId, bool $filterByBoletin = true)
    {
        return $this->repository->getAllByDocente($userId, $filterByBoletin);
    }

    public function getByDocenteAndGrupo(int $userId, int $grupoId)
    {
        return $this->repository->getByDocenteAndGrupo($userId, $grupoId);
    }

    public function assign(array $data)
    {
        // Verificar duplicados
        if ($this->repository->isAssigned($data['asignatura_grado_id'], $data['grupo_id'])) {
            throw new Exception("La asignatura-grado ya está asignada en este grupo.");
        }

        try {
            DB::beginTransaction();
            $assignment = $this->repository->create($data);
            DB::commit();
            return $assignment;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function assignBulk(array $data)
    {
        try {
            DB::beginTransaction();
            $results = [];
            $userId = $data['user_id'];
            $grupoId = $data['grupo_id'];

            foreach ($data['asignatura_grado_ids'] as $asignaturaGradoId) {
                // Check if already assigned
                $existing = $this->repository->findByAsignaturaAndGrupo($asignaturaGradoId, $grupoId);

                if ($existing) {
                    // Reassign to new user
                    $this->repository->update($existing->id, ['user_id' => $userId]);
                    $results[] = $existing;
                } else {
                    // Create new
                    $results[] = $this->repository->create([
                        'user_id' => $userId,
                        'grupo_id' => $grupoId,
                        'asignatura_grado_id' => $asignaturaGradoId,
                    ]);
                }
            }

            DB::commit();
            return $results;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updatePermisos(int $id, array $fechas)
    {
        try {
            DB::beginTransaction();
            $updated = $this->repository->update($id, $fechas);
            DB::commit();
            return $updated;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function unassign(int $id)
    {
        try {
            DB::beginTransaction();
            $deleted = $this->repository->delete($id);
            DB::commit();
            return $deleted;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getUnassignedVacancies(int $periodoLectivoId, ?int $gradoId = null): array
    {
        return $this->repository->getVacantes($periodoLectivoId, $gradoId);
    }

    public function generatePdfReport(int $userId, int $periodoLectivoId)
    {
        $docente = \App\Models\User::findOrFail($userId);
        $periodo = \App\Models\ConfPeriodoLectivo::find($periodoLectivoId);
        $asignaciones = $this->repository->getAllByDocenteAndPeriodo($userId, $periodoLectivoId);

        $datos = [
            'docente' => $docente,
            'asignaciones' => $asignaciones,
            'periodoNombre' => $periodo ? $periodo->nombre : 'N/A',
            'fecha_generacion' => now()->format('d/m/Y H:i:s'),
            'institucion' => config('app.nombre_institucion', 'MINISTERIO DE EDUCACIÓN')
        ];

        $html = view('pdf.docente-asignaciones', $datos)->render();

        $titulo = 'REPORTE DE CARGA ACADÉMICA';
        $subtitulo1 = 'Periodo: ' . ($periodo ? $periodo->nombre : 'Período no definido');
        $subtitulo2 = 'Docente: ' . $docente->nombre_completo;
        $nombreInstitucion = config('app.nombre_institucion', 'MINISTERIO DE EDUCACIÓN');

        $headerHtml = view()->make('pdf.header', compact('titulo', 'subtitulo1', 'subtitulo2', 'nombreInstitucion'))->render();

        $pdf = \Barryvdh\Snappy\Facades\SnappyPdf::loadHTML($html)
            ->setPaper('letter')
            ->setOrientation('portrait')
            ->setOption('margin-top', 35)
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 20)
            ->setOption('margin-left', 10)
            ->setOption('header-html', $headerHtml)
            ->setOption('header-spacing', 5)
            ->setOption('footer-left', 'Fecha y hora: [date] [time]')
            ->setOption('footer-right', 'Página [page] de [toPage]')
            ->setOption('footer-font-size', 8)
            ->setOption('footer-spacing', 5)
            ->setOption('load-error-handling', 'ignore');

        $nombreArchivo = 'carga_academica_' . str_replace(' ', '_', $docente->name) . '_' . now()->format('Ymd_His') . '.pdf';

        return $pdf->stream($nombreArchivo);
    }

    public function getByGrupo(int $grupoId)
    {
        return $this->repository->getByGrupo($grupoId);
    }

    public function updatePermisosMasivo(array $asignaciones)
    {
        try {
            DB::beginTransaction();
            foreach ($asignaciones as $asignacion) {
                if (isset($asignacion['id'])) {
                    $this->repository->update($asignacion['id'], [
                        'permiso_fecha_corte1' => $asignacion['permiso_fecha_corte1'] ?? null,
                        'permiso_fecha_corte2' => $asignacion['permiso_fecha_corte2'] ?? null,
                        'permiso_fecha_corte3' => $asignacion['permiso_fecha_corte3'] ?? null,
                        'permiso_fecha_corte4' => $asignacion['permiso_fecha_corte4'] ?? null,
                    ]);
                }
            }
            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getCargaAcademica(array $filters)
    {
        return $this->repository->getCargaAcademica($filters);
    }

    public function getFiltros(int $periodoLectivoId)
    {
        return $this->repository->getFiltros($periodoLectivoId);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PORTAL ADMINISTRATIVO
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Obtener todas las asignaciones del sistema con filtros opcionales.
     * No filtra por usuario autenticado – acceso administrativo completo.
     */
    public function getAllAssignments(array $filters = []): \Illuminate\Support\Collection
    {
        $query = DB::table('not_asignatura_grado_docente as agd')
            ->join('config_grupos as g', 'agd.grupo_id', '=', 'g.id')
            ->join('not_asignatura_grado as ag', 'agd.asignatura_grado_id', '=', 'ag.id')
            ->join('not_materias as m', 'ag.materia_id', '=', 'm.id')
            ->join('config_grado as cg', 'g.grado_id', '=', 'cg.id')
            ->join('config_seccion as cs', 'g.seccion_id', '=', 'cs.id')
            ->join('config_turnos as ct', 'g.turno_id', '=', 'ct.id')
            ->join('users as u', 'agd.user_id', '=', 'u.id')
            ->whereNull('agd.deleted_at')
            ->whereNull('g.deleted_at')
            ->whereNull('u.deleted_at');

        // Filtros opcionales
        if (!empty($filters['periodo_lectivo_id'])) {
            $query->where('g.periodo_lectivo_id', $filters['periodo_lectivo_id']);
        }
        if (!empty($filters['grupo_id'])) {
            $query->where('agd.grupo_id', $filters['grupo_id']);
        }
        if (!empty($filters['docente_id'])) {
            $query->where('agd.user_id', $filters['docente_id']);
        }
        if (!empty($filters['asignatura_grado_id'])) {
            $query->where('agd.asignatura_grado_id', $filters['asignatura_grado_id']);
        }

        return $query->select([
            'agd.id',
            'agd.id as id_asignacion',
            'agd.user_id',
            'agd.grupo_id',
            'agd.asignatura_grado_id',
            DB::raw("CONCAT(u.primer_nombre, ' ', u.primer_apellido) as docente_nombre"),
            'm.nombre as materia_nombre',
            'cg.nombre as grado_nombre',
            'cs.nombre as seccion_nombre',
            'ct.nombre as turno_nombre',
            DB::raw("CONCAT(cg.nombre, ' - ', cs.nombre, ' - ', ct.nombre) as grupo_nombre"),
            // Sub-select para conteo de estudiantes en el grupo
            DB::raw('(SELECT COUNT(*) FROM users_grupos ug WHERE ug.grupo_id = g.id AND ug.deleted_at IS NULL) as estudiantes_count'),
        ])->orderBy('cg.nombre')->orderBy('m.nombre')->get();
    }

    /**
     * Obtener opciones de filtros para el panel administrativo de notas.
     * Devuelve periodos, grupos y docentes disponibles.
     */
    public function getAdminFiltros(?int $periodoId = null): array
    {
        // Periodos lectivos (tabla correcta: conf_periodo_lectivos)
        $periodos = DB::table('conf_periodo_lectivos')
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->select('id', 'nombre')
            ->get();

        // Grupos (filtrados por periodo si se indica)
        $gruposQuery = DB::table('config_grupos as g')
            ->join('config_grado as cg', 'g.grado_id', '=', 'cg.id')
            ->join('config_seccion as cs', 'g.seccion_id', '=', 'cs.id')
            ->join('config_turnos as ct', 'g.turno_id', '=', 'ct.id')
            ->whereNull('g.deleted_at');

        if ($periodoId) {
            $gruposQuery->where('g.periodo_lectivo_id', $periodoId);
        }

        $grupos = $gruposQuery->select(
            'g.id',
            DB::raw("CONCAT(cg.nombre, ' - ', cs.nombre, ' - ', ct.nombre) as nombre"),
            'g.periodo_lectivo_id'
        )->orderBy('cg.orden')->orderBy('cs.orden')->orderBy('ct.nombre')->get();

        // Docentes con asignaciones activas
        $docentesQuery = DB::table('not_asignatura_grado_docente as agd')
            ->join('users as u', 'agd.user_id', '=', 'u.id')
            ->join('config_grupos as g', 'agd.grupo_id', '=', 'g.id')
            ->whereNull('agd.deleted_at')
            ->whereNull('u.deleted_at');

        if ($periodoId) {
            $docentesQuery->where('g.periodo_lectivo_id', $periodoId);
        }

        $docentes = $docentesQuery->select(
            'u.id',
            DB::raw("CONCAT(u.primer_apellido, IFNULL(CONCAT(' ', u.segundo_apellido), ''), ', ', u.primer_nombre, IFNULL(CONCAT(' ', u.segundo_nombre), '')) as nombre")
        )->groupBy('u.id', 'u.primer_nombre', 'u.segundo_nombre', 'u.primer_apellido', 'u.segundo_apellido')
         ->orderBy('u.primer_apellido')
         ->orderBy('u.primer_nombre')
         ->get();

        return [
            'periodos'  => $periodos,
            'grupos'    => $grupos,
            'docentes'  => $docentes,
        ];
    }
}
