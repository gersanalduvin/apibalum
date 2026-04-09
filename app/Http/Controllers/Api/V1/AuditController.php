<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UsersGrupo;
use App\Models\UsersAranceles;

use App\Models\ConfigGrado;
use App\Models\ConfigSeccion;
use App\Models\ConfigTurnos;
use App\Models\ConfigModalidad;
use App\Models\ConfPeriodoLectivo;
use App\Models\ConfigGrupo;
use App\Models\NotAsignaturaGrado;
use App\Models\NotAsignaturaGradoDocente;
use App\Models\ConfigNotEscalaDetalle;
use Illuminate\Http\JsonResponse;

class AuditController extends Controller
{
    private array $models = [
        'users' => User::class,
        'users_grupos' => UsersGrupo::class,
        'users_aranceles' => UsersAranceles::class,

        'config_grados' => ConfigGrado::class,
        'config_secciones' => ConfigSeccion::class,
        'config_turnos' => ConfigTurnos::class,
        'config_modalidades' => ConfigModalidad::class,
        'conf_periodo_lectivo' => ConfPeriodoLectivo::class,
        'config_grupos' => ConfigGrupo::class,
        'not_asignaturas_grados' => NotAsignaturaGrado::class,
        'not_asignatura_grado_docente' => NotAsignaturaGradoDocente::class,
        'not_escala_detalle' => ConfigNotEscalaDetalle::class,
        'config_aulas' => \App\Models\ConfigAula::class,
        'inventario_producto' => \App\Models\Producto::class,

        'config_catalogo_cuentas' => \App\Models\ConfigCatalogoCuentas::class,
    ];

    public function summary(string $model, int $id): JsonResponse
    {
        return $this->getSummary($model, $id);
    }

    public function getSummary(string $model, int $id): JsonResponse
    {
        if (!array_key_exists($model, $this->models)) {
            return response()->json([
                'success' => false,
                'message' => 'Modelo no válido para auditoría'
            ], 400);
        }

        $modelClass = $this->models[$model];
        $record = $modelClass::withTrashed()->find($id);

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Registro no encontrado'
            ], 404);
        }

        // Obtener la auditoría
        $audits = $record->audits()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        $historial = $audits->map(function ($audit) {
            $cambios = [];
            $old = $audit->old_values;
            $new = $audit->new_values;

            foreach ($new as $key => $value) {
                // Ignorar campos que no deben mostrarse si es necesario
                $oldValue = $old[$key] ?? null;
                if ($value != $oldValue) {
                    $cambios[] = [
                        'campo' => $key,
                        'de' => $oldValue,
                        'a' => $value
                    ];
                }
            }

            return [
                'event' => $audit->event,
                'fecha' => $audit->created_at->toIso8601String(),
                'usuario' => $audit->user ? ['nombre' => $audit->user->name] : ['nombre' => 'Sistema'],
                'cambios' => $cambios
            ];
        });

        $createdEvent = $audits->where('event', 'created')->first();

        // Si no hay evento created (migración o datos viejos), usar timestamps del registro si existen
        $createdAt = $createdEvent ? $createdEvent->created_at : $record->created_at;
        $creatorName = $createdEvent && $createdEvent->user ? $createdEvent->user->name : ($record->createdBy ? $record->createdBy->name : 'Sistema');

        $creado_por = [
            'nombre' => $creatorName,
            'created_at' => $createdAt ? $createdAt->toIso8601String() : null
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'creado_por' => $creado_por,
                'historial' => $historial
            ]
        ]);
    }
}
