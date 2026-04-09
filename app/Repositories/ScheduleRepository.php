<?php

namespace App\Repositories;

use App\Interfaces\ScheduleRepositoryInterface;
use App\Models\HorarioClase;
use App\Models\DocenteDisponibilidad;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ScheduleRepository implements ScheduleRepositoryInterface
{
    public function getScheduleByPeriod(int $periodoId): Collection
    {
        return HorarioClase::with(['grupo.grado', 'grupo.seccion', 'asignaturaGrado.materia', 'docente', 'aula'])
            ->where('periodo_lectivo_id', $periodoId)
            ->where(function ($q) {
                $q->whereNull('asignatura_grado_id')
                    ->orWhereHas('asignaturaGrado', function ($sq) {
                        $sq->where('horas_semanales', '>', 0);
                    });
            })
            ->get();
    }

    public function getScheduleByGroup(int $grupoId, int $periodoId): Collection
    {
        return HorarioClase::with(['grupo.grado', 'grupo.seccion', 'asignaturaGrado.materia', 'docente', 'aula'])
            ->where('periodo_lectivo_id', $periodoId)
            ->where('grupo_id', $grupoId)
            ->where(function ($q) {
                $q->whereNull('asignatura_grado_id')
                    ->orWhereHas('asignaturaGrado', function ($sq) {
                        $sq->where('horas_semanales', '>', 0);
                    });
            })
            ->orderBy('dia_semana')
            ->get();
    }

    public function getScheduleByTeacher(int $docenteId, int $periodoId, ?int $turnoId = null): Collection
    {
        $query = HorarioClase::with(['grupo.grado', 'grupo.seccion', 'asignaturaGrado.materia', 'aula'])
            ->where('periodo_lectivo_id', $periodoId)
            ->where('docente_id', $docenteId)
            ->orderBy('dia_semana');

        if ($turnoId) {
            $query->where(function ($q) use ($turnoId) {
                // Opción A: Tiene bloque horario definido para ese turno
                $q->whereHas('grupo', function ($gq) use ($turnoId) {
                    $gq->where('turno_id', $turnoId);
                });
            });
        }

        return $query->get();
    }

    public function getScheduleByRoom(int $aulaId, int $periodoId, ?int $turnoId = null): Collection
    {
        $query = HorarioClase::with(['grupo.grado', 'grupo.seccion', 'asignaturaGrado.materia', 'docente', 'bloqueHorario'])
            ->where('periodo_lectivo_id', $periodoId)
            ->where('aula_id', $aulaId)
            ->orderBy('dia_semana');

        if ($turnoId) {
            $query->where(function ($q) use ($turnoId) {
                $q->whereHas('grupo', function ($gq) use ($turnoId) {
                    $gq->where('turno_id', $turnoId);
                });
            });
        }

        return $query->get();
    }

    public function saveScheduleBlock(array $data): HorarioClase
    {
        return HorarioClase::create($data);
    }

    public function updateScheduleBlock(string $id, array $data): bool
    {
        return HorarioClase::where('id', $id)->update($data);
    }

    public function deleteScheduleBlock(string $id): bool
    {
        $block = HorarioClase::find($id);
        return $block ? $block->delete() : false;
    }

    public function clearSchedule(int $periodoId, ?int $grupoId = null): int
    {
        $query = HorarioClase::where('periodo_lectivo_id', $periodoId)
            ->where('is_fijo', false);

        if ($grupoId) {
            $query->where('grupo_id', $grupoId);
        }

        return $query->delete();
    }

    public function clearScheduleForGroup(int $grupoId, int $periodoId): void
    {
        $this->clearSchedule($periodoId, $grupoId);
    }

    public function bulkUpdate(array $blocks): int
    {
        $count = 0;
        DB::transaction(function () use ($blocks, &$count) {
            foreach ($blocks as $blockData) {
                $block = HorarioClase::find($blockData['id']);
                if ($block) {
                    $block->update([
                        'dia_semana' => $blockData['dia_semana'],
                        'hora_inicio_real' => $blockData['hora_inicio_real'],
                        'hora_fin_real' => $blockData['hora_fin_real'],
                    ]);
                    $count++;
                }
            }

            // Validar conflictos después de las actualizaciones
            foreach ($blocks as $blockData) {
                $block = HorarioClase::with(['docente', 'grupo', 'aula'])->find($blockData['id']);
                if (!$block) continue;

                // 1. Validar Docente
                if ($block->docente_id) {
                    $teacherConflict = $this->findConflictingTeacher(
                        $block->docente_id,
                        $block->dia_semana,
                        null,
                        $block->hora_inicio_real,
                        $block->hora_fin_real,
                        $block->id
                    );
                    if ($teacherConflict) {
                        throw new \Exception("Conflicto de Docente: {$block->docente->name} ya tiene clase asignada con el grupo {$teacherConflict->grupo->nombre} el día {$block->dia_semana} a las {$block->hora_inicio_real}");
                    }
                }

                // 2. Validar Aula
                if ($block->aula_id) {
                    $roomConflict = $this->findConflictingRoom(
                        $block->aula_id,
                        $block->dia_semana,
                        null,
                        $block->hora_inicio_real,
                        $block->hora_fin_real,
                        $block->id
                    );
                    if ($roomConflict) {
                        throw new \Exception("Conflicto de Aula: El aula {$block->aula->nombre} ya está ocupada por el grupo {$roomConflict->grupo->nombre} el día {$block->dia_semana} a las {$block->hora_inicio_real}");
                    }
                }

                // 3. Validar Grupo (Solapamiento interno)
                $groupConflict = $this->findConflictingGroup(
                    $block->grupo_id,
                    $block->dia_semana,
                    null,
                    $block->hora_inicio_real,
                    $block->hora_fin_real,
                    $block->id
                );
                if ($groupConflict) {
                    throw new \Exception("Conflicto de Grupo: El grupo {$block->grupo->nombre} ya tiene otra clase asignada el día {$block->dia_semana} a las {$block->hora_inicio_real}");
                }
            }
        });
        return $count;
    }

    // --- Validaciones ---

    public function findConflictingTeacher(int $docenteId, int $dia, ?int $bloqueId, ?string $startTime = null, ?string $endTime = null, ?int $excludeId = null): ?HorarioClase
    {
        $query = HorarioClase::where('horario_clases.docente_id', $docenteId)
            ->where('horario_clases.dia_semana', $dia)
            ->whereNull('horario_clases.deleted_at');

        if ($excludeId) {
            $query->where('horario_clases.id', '!=', $excludeId);
        }

        if ($startTime && $endTime) {
            $query->where(function ($q) use ($startTime, $endTime) {
                // Lógica de superposición: (InputStart < DbEnd) AND (InputEnd > DbStart)
                $q->where('horario_clases.hora_fin_real', '>', $startTime)
                    ->where('horario_clases.hora_inicio_real', '<', $endTime);
            });
        }

        return $query->first();
    }

    public function findConflictingRoom(int $aulaId, int $dia, ?int $bloqueId, ?string $startTime = null, ?string $endTime = null, ?int $excludeId = null): ?HorarioClase
    {
        $query = HorarioClase::where('horario_clases.aula_id', $aulaId)
            ->where('horario_clases.dia_semana', $dia)
            ->whereNull('horario_clases.deleted_at');

        if ($excludeId) {
            $query->where('horario_clases.id', '!=', $excludeId);
        }

        if ($startTime && $endTime) {
            $query->where(function ($q) use ($startTime, $endTime) {
                $q->where('horario_clases.hora_fin_real', '>', $startTime)
                    ->where('horario_clases.hora_inicio_real', '<', $endTime);
            });
        }

        return $query->first();
    }

    public function findConflictingGroup(int $grupoId, int $dia, ?int $bloqueId, ?string $startTime = null, ?string $endTime = null, ?int $excludeId = null): ?HorarioClase
    {
        $query = HorarioClase::where('horario_clases.grupo_id', $grupoId)
            ->where('horario_clases.dia_semana', $dia)
            ->where('horario_clases.es_simultanea', false)
            ->whereNull('horario_clases.deleted_at');

        if ($excludeId) {
            $query->where('horario_clases.id', '!=', $excludeId);
        }

        if ($startTime && $endTime) {
            $query->where(function ($q) use ($startTime, $endTime) {
                $q->where('horario_clases.hora_fin_real', '>', $startTime)
                    ->where('horario_clases.hora_inicio_real', '<', $endTime);
            });
        }

        return $query->first();
    }

    public function countAssignedHours(int $grupoId, int $asignaturaGradoId): int
    {
        return HorarioClase::where('grupo_id', $grupoId)
            ->where('asignatura_grado_id', $asignaturaGradoId)
            ->count();
    }


    // --- Métodos para DocenteDisponibilidad ---

    public function getDisponibilidad(int $docenteId, ?int $turnoId = null): Collection
    {
        $query = DocenteDisponibilidad::where('docente_id', $docenteId);
        if ($turnoId) {
            $query->where('turno_id', $turnoId);
        }
        return $query->get();
    }

    public function saveDisponibilidad(array $data): DocenteDisponibilidad
    {
        return DocenteDisponibilidad::create($data);
    }

    public function deleteDisponibilidad(int $id): bool
    {
        $item = DocenteDisponibilidad::find($id);
        return $item ? $item->delete() : false;
    }
}
