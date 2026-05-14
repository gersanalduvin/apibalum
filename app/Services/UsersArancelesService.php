<?php

namespace App\Services;

use App\Repositories\UsersArancelesRepository;
use App\Models\ConfigPlanPagoDetalle;
use App\Models\ConfPeriodoLectivo;
use App\Models\ConfigPlanPago;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\UsersAranceles;
use Barryvdh\Snappy\Facades\SnappyPdf;

class UsersArancelesService
{
    public function __construct(private UsersArancelesRepository $repository) {}

    /**
     * Obtener todos los aranceles paginados con filtros
     */
    public function getAllArancelesPaginated(array $filters = [], int $perPage = 15)
    {
        try {
            return $this->repository->getAllPaginated($perPage, $filters);
        } catch (Exception $e) {
            Log::error('Error al obtener aranceles paginados: ' . $e->getMessage());
            throw new Exception('Error al obtener los aranceles');
        }
    }

    /**
     * Obtener todos los aranceles sin paginación
     */
    public function getAllAranceles()
    {
        try {
            return $this->repository->getAll();
        } catch (Exception $e) {
            Log::error('Error al obtener todos los aranceles: ' . $e->getMessage());
            throw new Exception('Error al obtener los aranceles');
        }
    }

    /**
     * Obtener arancel por ID
     */
    public function getArancelById(int $id)
    {
        try {
            $arancel = $this->repository->find($id);

            if (!$arancel) {
                throw new Exception('Arancel no encontrado');
            }

            return $arancel;
        } catch (Exception $e) {
            Log::error('Error al obtener arancel por ID: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 1. Agregar Registro
     */
    public function createArancel(array $data)
    {
        try {
            DB::beginTransaction();

            // Validar que no exista el mismo rubro para el usuario si se proporciona
            if (isset($data['user_id']) && isset($data['rubro_id'])) {
                if ($this->repository->existeRubroParaUsuario($data['user_id'], $data['rubro_id'])) {
                    throw new Exception('Ya existe un registro para este usuario y rubro');
                }
            }

            // Calcular importe_total
            $importe = $data['importe'] ?? 0;
            $beca = $data['beca'] ?? 0;
            $descuento = $data['descuento'] ?? 0;
            $recargo = $data['recargo'] ?? 0;

            $data['importe_total'] = ($importe - $beca - $descuento) + $recargo;
            $data['saldo_actual'] = $data['importe_total'];
            $data['estado'] = $data['estado'] ?? 'pendiente';

            $arancel = $this->repository->create($data);

            DB::commit();
            return $arancel;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al crear arancel: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 2. Eliminar Registro
     */
    public function deleteArancel(int $id)
    {
        try {
            DB::beginTransaction();

            $arancel = $this->repository->find($id);
            if (!$arancel) {
                throw new Exception('Arancel no encontrado');
            }

            // Verificar si se puede eliminar (por ejemplo, si no tiene pagos)
            if ($arancel->saldo_pagado > 0) {
                throw new Exception('No se puede eliminar un arancel que ya tiene pagos realizados');
            }

            $deleted = $this->repository->delete($id);

            if (!$deleted) {
                throw new Exception('Error al eliminar el arancel');
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar arancel: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 3. Anular recargos
     * Actualiza los campos: recargo_pagado=recargo, saldo_actual recalcula. campos a actualizar: fecha_recargo_anulado, recargo_anulado_por, observacion_recargo.
     */
    public function anularRecargo(array $ids, array $data)
    {
        try {
            DB::beginTransaction();

            // Validar que los IDs existan
            $aranceles = $this->repository->findMultiple($ids);
            if ($aranceles->count() !== count($ids)) {
                throw new Exception('Algunos aranceles no fueron encontrados');
            }

            // Validar que tengan recargos
            $sinRecargo = $aranceles->filter(function ($arancel) {
                return $arancel->recargo <= 0;
            });

            if ($sinRecargo->count() > 0) {
                throw new Exception('Algunos aranceles seleccionados no tienen recargos');
            }

            // Preparar datos para la actualización
            $updateData = [
                'fecha_recargo_anulado' => $data['fecha_recargo_anulado'] ?? now()->format('Y-m-d'),
                'recargo_anulado_por' => $data['recargo_anulado_por'] ?? auth()->id(),
                'observacion_recargo' => $data['observacion_recargo'] ?? 'Recargo anulado'
            ];

            $updated = $this->repository->anularRecargo($ids, $updateData);

            DB::commit();
            return $updated;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al anular recargo: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 7. Aplicar Beca
     */
    public function aplicarBeca(array $ids, float $beca)
    {
        try {
            DB::beginTransaction();
            $updated = $this->repository->aplicarBeca($ids, $beca);
            DB::commit();
            return $updated;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al aplicar beca: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 8. Aplicar Descuento
     */
    public function aplicarDescuento(array $ids, float $descuento)
    {
        try {
            DB::beginTransaction();
            $updated = $this->repository->aplicarDescuento($ids, $descuento);
            DB::commit();
            return $updated;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al aplicar descuento: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 4. Exonerar
     * Actualiza los campos: estado=exonerado, fecha_exonerado, observacion_exonerado
     */
    public function exonerar(array $ids, array $data)
    {
        try {
            DB::beginTransaction();

            // Validar que los IDs existan
            $aranceles = $this->repository->findMultiple($ids);
            if ($aranceles->count() !== count($ids)) {
                throw new Exception('Algunos aranceles no fueron encontrados');
            }

            // Validar que no estén ya exonerados o pagados
            $noExonerables = $aranceles->filter(function ($arancel) {
                return in_array($arancel->estado, ['exonerado', 'pagado']);
            });

            if ($noExonerables->count() > 0) {
                throw new Exception('Algunos aranceles ya están exonerados o pagados');
            }

            // Preparar datos para la actualización
            $updateData = [
                'fecha_exonerado' => $data['fecha_exonerado'] ?? now()->format('Y-m-d'),
                'observacion_exonerado' => $data['observacion_exonerado'] ?? 'Arancel exonerado'
            ];

            $updated = $this->repository->exonerar($ids, $updateData);

            DB::commit();
            return $updated;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al exonerar aranceles: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 5. Aplicar plan de pago
     * Busca el detalle del plan de pago seleccionado y lo aplica a la tabla users_aranceles
     */
    public function aplicarPlanPago(int $planPagoId, int $userId)
    {
        try {
            DB::beginTransaction();

            // Obtener los detalles del plan de pago
            $detallesPlan = ConfigPlanPagoDetalle::where('plan_pago_id', $planPagoId)
                ->with(['planPago'])
                ->get();

            if ($detallesPlan->isEmpty()) {
                throw new Exception('No se encontraron detalles para el plan de pago especificado');
            }

            $registrosCreados = 0;
            $registrosOmitidos = 0;

            foreach ($detallesPlan as $detalle) {
                // Verificar si ya existe el rubro para el usuario
                if ($this->repository->existeRubroParaUsuario($userId, $detalle->id)) {
                    $registrosOmitidos++;
                    continue;
                }

                // Crear el registro en users_aranceles
                $data = [
                    'rubro_id' => $detalle->id,
                    'user_id' => $userId,
                    'importe' => $detalle->importe,
                    'importe_total' => $detalle->importe,
                    'saldo_actual' => $detalle->importe,
                    'estado' => 'pendiente',
                    'beca' => 0,
                    'descuento' => 0,
                    'recargo' => 0,
                    'saldo_pagado' => 0,
                    'recargo_pagado' => 0
                ];

                $this->repository->create($data);
                $registrosCreados++;
            }

            DB::commit();

            return [
                'registros_creados' => $registrosCreados,
                'registros_omitidos' => $registrosOmitidos,
                'total_detalles' => $detallesPlan->count()
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al aplicar plan de pago: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 6. Aplicar pago
     * Recibe array de IDs y actualiza: saldo_pagado=importe_total, recargo_pagado=recargo, saldo_actual=0, estado=pagado
     */
    public function aplicarPago(array $ids)
    {
        try {
            DB::beginTransaction();

            // Validar que los IDs existan
            $aranceles = $this->repository->findMultiple($ids);
            if ($aranceles->count() !== count($ids)) {
                throw new Exception('Algunos aranceles no fueron encontrados');
            }

            // Validar que no estén ya pagados o exonerados
            $noPagables = $aranceles->filter(function ($arancel) {
                return in_array($arancel->estado, ['pagado', 'exonerado']);
            });

            if ($noPagables->count() > 0) {
                throw new Exception('Algunos aranceles ya están pagados o exonerados');
            }

            // Validar que tengan saldo pendiente
            $sinSaldo = $aranceles->filter(function ($arancel) {
                return $arancel->saldo_actual <= 0;
            });

            if ($sinSaldo->count() > 0) {
                throw new Exception('Algunos aranceles no tienen saldo pendiente');
            }

            $updated = $this->repository->aplicarPago($ids);

            DB::commit();
            return $updated;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al aplicar pago: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Revertir pago de arancel
     * Cambia el estado a pendiente y resetea saldos pagados
     */
    public function revertirArancel(int $id)
    {
        try {
            DB::beginTransaction();

            $arancel = $this->repository->find($id);
            if (!$arancel) {
                throw new Exception('Arancel no encontrado');
            }

            if ($arancel->estado !== 'pagado') {
                throw new Exception('Solo se pueden revertir aranceles con estado pagado');
            }

            $success = $this->repository->revertirPago($id);

            if (!$success) {
                throw new Exception('Error al revertir el pago del arancel');
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al revertir arancel: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener aranceles por usuario
     */
    public function getArancelesByUser(int $userId)
    {
        try {
            return $this->repository->getByUser($userId);
        } catch (Exception $e) {
            Log::error('Error al obtener aranceles por usuario: ' . $e->getMessage());
            throw new Exception('Error al obtener los aranceles del usuario');
        }
    }

    /**
     * Obtener aranceles pendientes por usuario
     */
    public function getArancelesPendientesByUser(int $userId)
    {
        try {
            return $this->repository->getPendientesByUser($userId);
        } catch (Exception $e) {
            Log::error('Error al obtener aranceles pendientes por usuario: ' . $e->getMessage());
            throw new Exception('Error al obtener los aranceles pendientes del usuario');
        }
    }

    /**
     * Obtener aranceles con recargo
     */
    public function getArancelesConRecargo()
    {
        try {
            return $this->repository->getConRecargo();
        } catch (Exception $e) {
            Log::error('Error al obtener aranceles con recargo: ' . $e->getMessage());
            throw new Exception('Error al obtener los aranceles con recargo');
        }
    }

    /**
     * Obtener aranceles con saldo pendiente
     */
    public function getArancelesConSaldoPendiente()
    {
        try {
            return $this->repository->getConSaldoPendiente();
        } catch (Exception $e) {
            Log::error('Error al obtener aranceles con saldo pendiente: ' . $e->getMessage());
            throw new Exception('Error al obtener los aranceles con saldo pendiente');
        }
    }

    /**
     * Obtener estadísticas de aranceles
     */
    public function getEstadisticas()
    {
        try {
            return $this->repository->getEstadisticas();
        } catch (Exception $e) {
            Log::error('Error al obtener estadísticas de aranceles: ' . $e->getMessage());
            throw new Exception('Error al obtener las estadísticas');
        }
    }

    /**
     * Aplica recargos a los aranceles pendientes que ya vencieron.
     * Si se provee un $userId, solo escanea los de ese usuario específico.
     */
    public function aplicarRecargosVencidos(?int $userId = null): array
    {
        try {
            $hoy = now()->toDateString();
            
            // Obtener todos los aranceles pendientes con saldo mayor a 0 y que no tengan recargo aplicado
            $query = UsersAranceles::where('estado', 'pendiente')
                ->where('saldo_actual', '>', 0)
                ->where('recargo', 0) // Asumimos recargo único
                ->whereHas('rubro', function ($q) use ($hoy) {
                    $q->whereNotNull('fecha_vencimiento')
                          ->where('fecha_vencimiento', '<', $hoy)
                          ->where(function ($q2) {
                              $q2->where('importe_recargo', '>', 0)
                                 ->orWhere('tipo_recargo', 'porcentaje');
                          });
                });

            if ($userId) {
                $query->where('user_id', $userId);
            }

            $arancelesPendientes = $query->with('rubro')->get();

            $procesados = 0;
            $errores = 0;

            foreach ($arancelesPendientes as $arancel) {
                try {
                    $detallePlan = $arancel->rubro;
                    
                    if (!$detallePlan || !$detallePlan->fecha_vencimiento || $detallePlan->fecha_vencimiento->toDateString() >= $hoy) {
                        continue;
                    }

                    $importeRecargo = (float) $detallePlan->importe_recargo;
                    $tipoRecargo = $detallePlan->tipo_recargo;
                    
                    if ($importeRecargo <= 0 && $tipoRecargo !== 'porcentaje') {
                        continue;
                    }

                    $nuevoRecargo = 0;
                    
                    // Calcular el recargo en base al tipo
                    if ($tipoRecargo === 'porcentaje') {
                        // El porcentaje se calcula sobre el importe base (importe - beca - descuento)
                        // o sobre el importe total actual sin recargo. Usaremos importe_total que en este momento
                        // no incluye recargo (ya que recargo == 0).
                        $base = $arancel->importe - $arancel->beca - $arancel->descuento;
                        $nuevoRecargo = max(0, $base * ($importeRecargo / 100));
                    } else {
                        // Monto fijo
                        $nuevoRecargo = $importeRecargo;
                    }

                    if ($nuevoRecargo > 0) {
                        DB::beginTransaction();
                        
                        $arancel->recargo = $nuevoRecargo;
                        $arancel->importe_total = ($arancel->importe - $arancel->beca - $arancel->descuento) + $arancel->recargo;
                        $arancel->saldo_actual = max(0, $arancel->importe_total - $arancel->saldo_pagado - $arancel->recargo_pagado);
                        // En un cron, auth()->id() puede ser nulo, por lo que actualizamos a un usuario de sistema (1) o dejamos el existente
                        $arancel->updated_by = auth()->id() ?? 1; 
                        
                        $arancel->save();
                        
                        DB::commit();
                        $procesados++;
                    }
                } catch (Exception $e) {
                    DB::rollBack();
                    Log::error("Error al aplicar recargo al arancel ID {$arancel->id}: " . $e->getMessage());
                    $errores++;
                }
            }

            return [
                'procesados' => $procesados,
                'errores' => $errores,
                'total_pendientes_encontrados' => $arancelesPendientes->count()
            ];
            
        } catch (Exception $e) {
            Log::error('Error general al ejecutar aplicarRecargosVencidos: ' . $e->getMessage());
            throw new Exception('Error al aplicar los recargos vencidos');
        }
    }

    /**
     * Obtener todos los períodos lectivos
     */
    public function getPeriodosLectivos()
    {
        try {
            return ConfPeriodoLectivo::select('id', 'uuid', 'nombre', 'periodo_nota', 'periodo_matricula')
                ->orderBy('nombre')
                ->get()
                ->map(function ($periodo) {
                    return [
                        'id' => $periodo->id,
                        'uuid' => $periodo->uuid,
                        'nombre' => $periodo->nombre,
                        'periodo_nota' => $periodo->periodo_nota,
                        'periodo_matricula' => $periodo->periodo_matricula
                    ];
                });
        } catch (Exception $e) {
            Log::error('Error al obtener períodos lectivos: ' . $e->getMessage());
            throw new Exception('Error al obtener los períodos lectivos');
        }
    }

    /**
     * Obtener planes de pago por período lectivo
     */
    public function getPlanesPagoPorPeriodo(int $periodoLectivoId)
    {
        try {
            return ConfigPlanPago::with(['periodoLectivo', 'detalles'])
                ->where('periodo_lectivo_id', $periodoLectivoId)
                ->where('estado', true)
                ->select('id', 'uuid', 'nombre', 'estado', 'periodo_lectivo_id')
                ->orderBy('nombre')
                ->get()
                ->map(function ($plan) {
                    return [
                        'id' => $plan->id,
                        'uuid' => $plan->uuid,
                        'nombre' => $plan->nombre,
                        'estado' => $plan->estado,
                        'estado_text' => $plan->estado ? 'Activo' : 'Inactivo',
                        'periodo_lectivo' => [
                            'id' => $plan->periodoLectivo->id,
                            'nombre' => $plan->periodoLectivo->nombre
                        ],
                        'total_detalles' => $plan->detalles->count(),
                        'total_importe' => $plan->detalles->sum('importe')
                    ];
                });
        } catch (Exception $e) {
            Log::error('Error al obtener planes de pago por período lectivo: ' . $e->getMessage());
            throw new Exception('Error al obtener los planes de pago');
        }
    }

    /**
     * Actualizar arancel (método auxiliar)
     */
    public function updateArancel(int $id, array $data)
    {
        try {
            DB::beginTransaction();

            $arancel = $this->repository->find($id);
            if (!$arancel) {
                throw new Exception('Arancel no encontrado');
            }

            // Recalcular importe_total si se modifican los campos relacionados
            if (isset($data['importe']) || isset($data['beca']) || isset($data['descuento']) || isset($data['recargo'])) {
                $importe = $data['importe'] ?? $arancel->importe;
                $beca = $data['beca'] ?? $arancel->beca;
                $descuento = $data['descuento'] ?? $arancel->descuento;
                $recargo = $data['recargo'] ?? $arancel->recargo;

                $data['importe_total'] = ($importe - $beca - $descuento) + $recargo;

                // Recalcular saldo_actual
                $data['saldo_actual'] = $data['importe_total'] - $arancel->saldo_pagado - $arancel->recargo_pagado;
            }

            $updated = $this->repository->update($id, $data);

            if (!$updated) {
                throw new Exception('Error al actualizar el arancel');
            }

            DB::commit();
            return $this->repository->find($id);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar arancel: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generar reporte PDF de aranceles del usuario
     */
    public function generarPdfReporte(int $userId, array $filters = [])
    {
        try {
            $user = User::findOrFail($userId);

            $query = UsersAranceles::where('users_aranceles.user_id', $userId)
                ->select('users_aranceles.*')
                ->leftJoin('config_plan_pago_detalle', 'users_aranceles.rubro_id', '=', 'config_plan_pago_detalle.id')
                ->with(['rubro.planPago', 'arancel']);

            if (!empty($filters['estado'])) {
                if (strtolower($filters['estado']) === 'pagado') {
                    $query->where('users_aranceles.estado', '!=', 'pendiente');
                } else {
                    $query->where('users_aranceles.estado', $filters['estado']);
                }
            }

            if (!empty($filters['fecha_inicio'])) {
                $query->whereDate('users_aranceles.created_at', '>=', $filters['fecha_inicio']);
            }

            if (!empty($filters['fecha_fin'])) {
                $query->whereDate('users_aranceles.created_at', '<=', $filters['fecha_fin']);
            }

            $aranceles = $query->orderBy('config_plan_pago_detalle.orden_mes', 'asc')
                ->orderBy('users_aranceles.created_at', 'desc')
                ->get();

            $datos = [
                'user' => $user,
                'aranceles' => $aranceles,
                'estado' => $filters['estado'] ?? 'TODOS',
                'fecha_generacion' => now()->format('d/m/Y H:i:s'),
                'institucion' => config('app.nombre_institucion', 'MINISTERIO DE EDUCACIÓN')
            ];

            $titulo = 'REPORTE DE ARANCELES';
            
            // Determinar el periodo lectivo de los aranceles para buscar la matrícula correcta
            $periodoId = null;
            if ($aranceles->isNotEmpty()) {
                $firstWithPeriod = $aranceles->first(function($a) {
                    return $a->rubro?->planPago !== null;
                });
                if ($firstWithPeriod) {
                    $periodoId = $firstWithPeriod->rubro->planPago->periodo_lectivo_id;
                }
            }
            
            // Si no se encontró periodo en los aranceles, buscar el periodo lectivo actual por defecto
            if (!$periodoId) {
                $periodoId = \App\Models\ConfPeriodoLectivo::where('periodo_matricula', true)->first()?->id;
            }

            // Buscar la matrícula del alumno para el periodo determinado
            $matricula = $user->grupos()
                ->where('estado', 'activo')
                ->when($periodoId, function($q) use ($periodoId) {
                    return $q->where('periodo_lectivo_id', $periodoId);
                })
                ->with(['grado', 'grupo.seccion'])
                ->latest()
                ->first();
            
            $grado = $matricula ? $matricula->grado : null;
            $grupo = $matricula ? $matricula->grupo : null;
            $perfil = ($grado && $grado->formato) ? $grado->formato : 'cuantitativo';
            
            $datos['grado'] = $grado;
            $datos['grupo'] = $grupo;
            $datos['perfil'] = $perfil;

            $html = view('pdf.aranceles-usuario', $datos)->render();

            $subtitulo1 = null; // Eliminamos el nombre del alumno del encabezado derecho (se marcó en rojo)
            $subtitulo2 = 'Estado: ' . strtoupper($filters['estado'] ?? 'TODOS');
            $nombreInstitucion = config("institucion.{$perfil}.nombre", $datos['institucion']);

            $headerHtml = view()->make('pdf.header', compact('titulo', 'subtitulo1', 'subtitulo2', 'nombreInstitucion', 'perfil'))->render();

            $pdf = SnappyPdf::loadHTML($html)
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

            $nombreArchivo = 'reporte_aranceles_' . str_replace(' ', '_', $user->name) . '_' . now()->format('Ymd_His') . '.pdf';

            return $pdf->stream($nombreArchivo);
        } catch (Exception $e) {
            Log::error('Error al generar PDF de aranceles: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generar reporte PDF de aranceles para todos los alumnos de un grupo
     */
    public function generarPdfReporteGrupo(int $grupoId, array $filters = [])
    {
        try {
            $matriculas = \App\Models\UsersGrupo::where('grupo_id', $grupoId)
                ->where('estado', 'activo')
                ->with(['user', 'grado', 'grupo.seccion'])
                ->get()
                ->sortBy(function($m) {
                    return trim(($m->user->primer_apellido ?? '') . ' ' . ($m->user->segundo_apellido ?? '') . ' ' . ($m->user->primer_nombre ?? '') . ' ' . ($m->user->segundo_nombre ?? ''));
                });

            \Illuminate\Support\Facades\Log::info('Generando PDF para grupo ' . $grupoId . '. Alumnos: ' . $matriculas->count());

            if ($matriculas->isEmpty()) {
                throw new Exception('No hay alumnos activos en este grupo.');
            }

            $perfil = 'cuantitativo';
            $nombreInstitucion = config('app.nombre_institucion', 'MINISTERIO DE EDUCACIÓN');
            $gradoNombre = '';
            $seccionNombre = '';

            $studentsData = [];

            foreach ($matriculas as $index => $matricula) {
                $user = $matricula->user;
                $grado = $matricula->grado;
                $grupo = $matricula->grupo;
                
                if ($index === 0) {
                    $perfil = ($grado && $grado->formato) ? $grado->formato : 'cuantitativo';
                    $nombreInstitucion = config("institucion.{$perfil}.nombre", $nombreInstitucion);
                    $gradoNombre = $grado ? $grado->nombre : 'N/A';
                    $seccionNombre = $grupo->seccion->nombre ?? '';
                }

                $query = UsersAranceles::where('users_aranceles.user_id', $user->id)
                    ->select('users_aranceles.*')
                    ->leftJoin('config_plan_pago_detalle', 'users_aranceles.rubro_id', '=', 'config_plan_pago_detalle.id')
                    ->with(['rubro.planPago', 'arancel']);

                if (!empty($filters['estado'])) {
                    if (strtolower($filters['estado']) === 'pagado') {
                        $query->where('users_aranceles.estado', '!=', 'pendiente');
                    } else {
                        $query->where('users_aranceles.estado', $filters['estado']);
                    }
                }

                $aranceles = $query->orderBy('config_plan_pago_detalle.orden_mes', 'asc')
                    ->orderBy('users_aranceles.created_at', 'desc')
                    ->get();

                $studentsData[] = [
                    'user' => $user,
                    'aranceles' => $aranceles,
                    'grado' => $grado,
                    'grupo' => $grupo
                ];
            }

            $titulo = 'REPORTE DE ARANCELES POR GRUPO';
            $subtitulo1 = null;
            $subtitulo2 = null;

            $html = view('pdf.aranceles-grupo', [
                'students' => $studentsData,
                'perfil' => $perfil,
                'estado' => $filters['estado'] ?? 'TODOS'
            ])->render();

            $headerHtml = view()->make('pdf.header', compact('titulo', 'subtitulo1', 'subtitulo2', 'nombreInstitucion', 'perfil'))->render();

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

            $nombreArchivo = 'reporte_aranceles_grupo_' . str_replace(' ', '_', $gradoNombre) . '_' . now()->format('Ymd_His') . '.pdf';

            return $pdf->stream($nombreArchivo);
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al generar PDF de aranceles por grupo: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            throw $e;
        }
    }
}
