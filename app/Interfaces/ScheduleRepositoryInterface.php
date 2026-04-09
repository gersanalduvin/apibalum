<?php

namespace App\Interfaces;

use App\Models\HorarioClase;
use App\Models\DocenteDisponibilidad;
use Illuminate\Support\Collection;

interface ScheduleRepositoryInterface
{
    public function getScheduleByPeriod(int $periodoId): Collection;
    public function getScheduleByGroup(int $grupoId, int $periodoId): Collection;
    public function getScheduleByTeacher(int $docenteId, int $periodoId, ?int $turnoId = null): Collection;
    public function getScheduleByRoom(int $aulaId, int $periodoId, ?int $turnoId = null): Collection;

    public function saveScheduleBlock(array $data): HorarioClase;
    public function updateScheduleBlock(string $id, array $data): bool;
    public function deleteScheduleBlock(string $id): bool;
    public function clearSchedule(int $periodoId, ?int $grupoId = null): int;
    public function clearScheduleForGroup(int $grupoId, int $periodoId): void;
    public function bulkUpdate(array $blocks): int;

    // Métodos para validaciones
    public function findConflictingTeacher(int $docenteId, int $dia, ?int $bloqueId, ?string $startTime = null, ?string $endTime = null, ?int $excludeId = null): ?HorarioClase;
    public function findConflictingRoom(int $aulaId, int $dia, ?int $bloqueId, ?string $startTime = null, ?string $endTime = null, ?int $excludeId = null): ?HorarioClase;
    public function findConflictingGroup(int $grupoId, int $dia, ?int $bloqueId, ?string $startTime = null, ?string $endTime = null, ?int $excludeId = null): ?HorarioClase;
    public function countAssignedHours(int $grupoId, int $asignaturaGradoId): int;


    // Métodos para DocenteDisponibilidad
    public function getDisponibilidad(int $docenteId, ?int $turnoId = null): Collection;
    public function saveDisponibilidad(array $data): DocenteDisponibilidad; // Requires import
    public function deleteDisponibilidad(int $id): bool;
}
