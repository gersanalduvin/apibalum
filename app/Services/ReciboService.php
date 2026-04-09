<?php

namespace App\Services;

use App\Repositories\ReciboRepository;
use App\Repositories\UsersArancelesRepository;
use App\Models\Recibo;
use App\Models\ReciboDetalle;
use App\Models\ReciboFormaPago;
use Illuminate\Support\Facades\DB;
use Exception;
use Barryvdh\Snappy\Facades\SnappyPdf;

class ReciboService
{
    public function __construct(
        private ReciboRepository $reciboRepository,
        private ConfigParametrosService $configParametrosService,
        private MovimientoInventarioService $movimientoInventarioService,
        private UsersArancelesRepository $usersArancelesRepository
    ) {}

    public function getAllRecibosPaginated(array $filters = [], int $perPage = 15)
    {
        return $this->reciboRepository->getAllPaginated($perPage, $filters);
    }

    public function createRecibo(array $data): Recibo
    {
        return DB::transaction(function () use ($data) {
            $config = $this->configParametrosService->getConfigParametros();
            $data['tasa_cambio'] = $config->tasa_cambio_dolar ?? 0.0000;

            if (empty($data['fecha'])) {
                $data['fecha'] = now()->format('Y-m-d');
            }

            if (empty($data['estado'])) {
                $data['estado'] = 'activo';
            }

            $detalles = $data['detalles'] ?? [];
            $formasPago = $data['formas_pago'] ?? [];

            unset($data['detalles'], $data['formas_pago']);

            $recibo = $this->reciboRepository->create($data);

            $total = 0;
            $idx = 0;
            foreach ($detalles as $detalle) {
                $cantidad = (float) ($detalle['cantidad'] ?? 0);
                $monto = (float) ($detalle['monto'] ?? 0);
                $descuento = (float) ($detalle['descuento'] ?? 0); // Assuming discount can be passed in detalle
                $totalDetalle = ($cantidad * $monto) - $descuento;

                // Resolver el rubro_id (debe ser el ID de users_aranceles, no el de config_plan_pago_detalle)
                $rubroIdToSave = $detalle['rubro_id'] ?? null;
                if ($rubroIdToSave && !empty($data['user_id'])) {
                    $arancel = $this->usersArancelesRepository->findByUserAndRubro((int)$data['user_id'], (int)$rubroIdToSave);
                    if ($arancel) {
                        $rubroIdToSave = $arancel->id;
                    }
                }

                $detalleModel = new ReciboDetalle([
                    'recibo_id' => $recibo->id,
                    'rubro_id' => $rubroIdToSave,
                    'producto_id' => $detalle['producto_id'] ?? null,
                    'aranceles_id' => $detalle['aranceles_id'] ?? null,
                    'concepto' => $detalle['concepto'] ?? null,
                    'cantidad' => $cantidad,
                    'monto' => $monto,
                    'descuento' => $descuento,
                    'total' => $totalDetalle,
                    'tipo_pago' => $detalle['tipo_pago'] ?? 'total'
                ]);
                $detalleModel->save();

                // Actualizar inventario si hay producto
                if (!empty($detalle['producto_id']) && $cantidad > 0) {
                    $producto = \App\Models\Producto::find($detalle['producto_id']);
                    $costoUnitario = $producto ? ($producto->costo_promedio ?? 0) : 0;

                    $docNumero = ($data['numero_recibo'] ?? 'REC') . '-' . $recibo->id . '-' . $idx;
                    $this->movimientoInventarioService->aplicarMovimientoProducto(
                        (int)$detalle['producto_id'],
                        'salida',
                        (float)$cantidad,
                        (float)$costoUnitario,
                        [
                            'documento_tipo' => 'recibo',
                            'documento_numero' => $docNumero,
                            'documento_fecha' => now(),
                            'observaciones' => 'Salida por recibo '
                        ]
                    );
                }

                // Actualizar arancel del usuario si el detalle lleva rubro_id
                if (!empty($detalle['rubro_id']) && !empty($data['user_id'])) {
                    $userId = (int) $data['user_id'];
                    $rubroId = (int) $detalle['rubro_id'];
                    $arancel = $this->usersArancelesRepository->findByUserAndRubro($userId, $rubroId);
                    if ($arancel) {
                        $nuevoSaldoPagado = min((float)$arancel->saldo_pagado + (float)$totalDetalle, (float)$arancel->importe_total);
                        $nuevoSaldoActual = max(0.0, (float)$arancel->importe_total - $nuevoSaldoPagado);
                        $nuevoEstado = $nuevoSaldoActual <= 0.0 ? 'pagado' : 'pendiente';
                        $this->usersArancelesRepository->update((int)$arancel->id, [
                            'saldo_pagado' => $nuevoSaldoPagado,
                            'saldo_actual' => $nuevoSaldoActual,
                            'estado' => $nuevoEstado,
                        ]);
                    }
                }

                // 2024-01-05: Soporte para Combos (Arancel -> Múltiples Productos)
                // Si el detalle tiene un arancel_id, buscamos si tiene productos asociados para descargarlos
                if (!empty($detalle['aranceles_id'])) {
                    $arancel = \App\Models\ConfigArancel::with('productos')->find($detalle['aranceles_id']);
                    if ($arancel && $arancel->productos->isNotEmpty()) {
                        foreach ($arancel->productos as $prodCombo) {
                            $cantidadCombo = (float) $prodCombo->pivot->cantidad;
                            $cantidadTotalDescarga = $cantidadCombo * $cantidad; // Cantidad del combo * cantidad vendida

                            $docNumero = ($data['numero_recibo'] ?? 'REC') . '-' . $recibo->id . '-' . $idx . '-COMBO-' . $prodCombo->id;

                            // Registramos la salida sin costo (o costo 0) al recibo visible,
                            // pero el movimiento interno maneja su propio costo promedio si es necesario.
                            // Aquí pasamos costo 0 porque el cobro ya se hizo en el Arancel principal.
                            $this->movimientoInventarioService->aplicarMovimientoProducto(
                                (int)$prodCombo->id,
                                'salida',
                                (float)$cantidadTotalDescarga,
                                (float)($prodCombo->precio_venta ?? 0), // Usamos el precio de venta del producto para referencia en kardex
                                [
                                    'documento_tipo' => 'recibo_combo',
                                    'documento_numero' => $docNumero,
                                    'documento_fecha' => now(),
                                    'observaciones' => 'Salida por combo/arancel: ' . $arancel->nombre
                                ]
                            );
                        }
                    }
                }

                $total += $totalDetalle;
                $idx++;
            }

            // Formas de pago
            $totalFormasPago = 0;
            foreach ($formasPago as $fp) {
                $montoFp = (float) ($fp['monto'] ?? 0);
                $fpModel = new ReciboFormaPago([
                    'recibo_id' => $recibo->id,
                    'forma_pago_id' => $fp['forma_pago_id'],
                    'monto' => $montoFp,
                ]);
                $fpModel->save();
                $totalFormasPago += $montoFp;
            }

            // Actualizar total del recibo si no se proporcionó o difiere
            if (empty($data['total']) || (float)$recibo->total !== (float)$total) {
                $recibo->update(['total' => $total]);
                $recibo->refresh();
            }

            // Actualizar consecutivos en config_parametros según tipo de recibo
            $numero = (int) ($data['numero_recibo'] ?? $recibo->numero_recibo ?? 0);
            $siguiente = $numero + 1;
            if (($data['tipo'] ?? $recibo->tipo) === 'interno') {
                $this->configParametrosService->updateOrCreateConfigParametros([
                    'consecutivo_recibo_interno' => $siguiente,
                ]);
            } else {
                $this->configParametrosService->updateOrCreateConfigParametros([
                    'consecutivo_recibo_oficial' => $siguiente,
                ]);
            }

            return $recibo->load(['usuario', 'detalles.producto', 'formasPago']);
        });
    }

    public function anularRecibo(int $id): Recibo
    {
        return DB::transaction(function () use ($id) {
            $recibo = $this->reciboRepository->find($id);
            if (!$recibo) {
                throw new Exception('Recibo no encontrado');
            }

            // Validar restricción de tiempo para la anulación
            $today = \Carbon\Carbon::today()->toDateString();
            $reciboDate = $recibo->fecha ? $recibo->fecha->toDateString() : $recibo->created_at->toDateString();
            $user = auth()->user();
            if ($reciboDate !== $today && !$user->superadmin && !$user->can('recibos.anular_cualquier_fecha')) {
                throw new Exception('No tiene permisos para anular recibos de fechas anteriores.');
            }

            // Evitar doble procesamiento
            if ($recibo->estado === 'anulado') {
                return $recibo;
            }

            // Revertir inventario: por cada detalle con producto, registrar ENTRADA
            $idx = 0;
            foreach ($recibo->detalles as $detalle) {
                if (!empty($detalle->producto_id) && (float)$detalle->cantidad > 0) {
                    $cantidad = (float) $detalle->cantidad;
                    $producto = \App\Models\Producto::find($detalle->producto_id);
                    $costoUnitario = $producto ? ($producto->costo_promedio ?? 0) : 0;
                    $docNumero = 'ANUL:' . ($recibo->numero_recibo ?? 'REC') . '-' . $recibo->id . '-' . $idx;
                    $this->movimientoInventarioService->aplicarMovimientoProducto(
                        (int)$detalle->producto_id,
                        'entrada',
                        (float)$cantidad,
                        (float)$costoUnitario,
                        [
                            'documento_tipo' => 'anulacion_recibo',
                            'documento_numero' => $docNumero,
                            'documento_fecha' => now(),
                            'observaciones' => 'Entrada por anulación de recibo '
                        ]
                    );
                }

                // Restaurar inventario para COMBOS (Arancel -> Múltiples Productos)
                if (!empty($detalle->aranceles_id) && (float)$detalle->cantidad > 0) {
                    $arancel = \App\Models\ConfigArancel::with('productos')->find($detalle->aranceles_id);
                    if ($arancel && $arancel->productos->isNotEmpty()) {
                        foreach ($arancel->productos as $prodCombo) {
                            $cantidadCombo = (float) $prodCombo->pivot->cantidad;
                            $cantidadTotalRegreso = $cantidadCombo * (float)$detalle->cantidad;

                            $docNumero = 'ANUL:' . ($recibo->numero_recibo ?? 'REC') . '-' . $recibo->id . '-' . $idx . '-COMBO-' . $prodCombo->id;

                            $this->movimientoInventarioService->aplicarMovimientoProducto(
                                (int)$prodCombo->id,
                                'entrada',
                                (float)$cantidadTotalRegreso,
                                (float)($prodCombo->precio_venta ?? 0),
                                [
                                    'documento_tipo' => 'anulacion_recibo_combo',
                                    'documento_numero' => $docNumero,
                                    'documento_fecha' => now(),
                                    'observaciones' => 'Entrada por anulación de recibo (combo): ' . $arancel->nombre
                                ]
                            );
                        }
                    }
                }

                // Revertir pago de Rubro/Mensualidad (Arancel de Usuario update)
                if (!empty($detalle->rubro_id) && !empty($recibo->user_id)) {
                    $arancelUsuario = $this->usersArancelesRepository->find((int)$detalle->rubro_id);

                    // Fallback por si en datos antiguos rubro_id era realmente el id del rubro y no de users_aranceles
                    if (!$arancelUsuario || $arancelUsuario->user_id !== $recibo->user_id) {
                        $arancelUsuario = $this->usersArancelesRepository->findByUserAndRubro((int)$recibo->user_id, (int)$detalle->rubro_id);
                    }
                    if ($arancelUsuario) {
                        $montoAnular = (float)$detalle->total;

                        // Revertir saldos
                        $nuevoSaldoPagado = max(0.0, (float)$arancelUsuario->saldo_pagado - $montoAnular);
                        // Recalcular saldo actual basado en el importe total para asegurar consistencia
                        $nuevoSaldoActual = max(0.0, (float)$arancelUsuario->importe_total - $nuevoSaldoPagado);

                        $nuevoEstado = $nuevoSaldoActual > 0.001 ? 'pendiente' : 'pagado';

                        $this->usersArancelesRepository->update($arancelUsuario->id, [
                            'saldo_pagado' => $nuevoSaldoPagado,
                            'saldo_actual' => $nuevoSaldoActual,
                            'estado' => $nuevoEstado,
                        ]);
                    }
                }

                $idx++;
            }

            // Actualizar estado del recibo
            $this->reciboRepository->update($id, ['estado' => 'anulado']);
            return $this->reciboRepository->find($id);
        });
    }

    public function deleteRecibo(int $id): void
    {
        $recibo = $this->reciboRepository->find($id);
        if (!$recibo) {
            throw new Exception('Recibo no encontrado');
        }

        if ($recibo->estado !== 'anulado') {
            throw new Exception('Solo se pueden eliminar recibos anulados.');
        }

        // Registrar quién elimina (Soft delete)
        $recibo->deleted_by = auth()->id();
        $recibo->save();

        $this->reciboRepository->delete($id);
    }

    public function generarPdf(int $id)
    {
        $recibo = $this->reciboRepository->find($id);
        if (!$recibo) {
            throw new Exception('Recibo no encontrado');
        }

        // Determinar perfil dinámicamente
        $perfil = 'cuantitativo'; // Default
        if ($recibo->tipo === 'interno') {
            $perfil = 'cualitativo';
        } else if ($recibo->usuario && $recibo->usuario->grado_actual) {
            // Intentar determinar por el grado del usuario si está disponible
            $formato = $recibo->usuario->grado_actual->grado->formato ?? 'cuantitativo';
            $perfil = $formato === 'cualitativo' ? 'cualitativo' : 'cuantitativo';
        } else if ($recibo->grado) {
            // Fallback: buscar por nombre de grado
            $gradoModel = \App\Models\ConfigGrado::where('nombre', $recibo->grado)->first();
            if ($gradoModel) {
                $perfil = $gradoModel->formato === 'cualitativo' ? 'cualitativo' : 'cuantitativo';
            }
        }

        $datos = [
            'recibo' => $recibo,
            'perfil' => $perfil,
            'cantidad_letras' => \App\Utils\NumeroALetras::convertir((float)$recibo->total),
        ];

        $html = view('pdf.recibo-moderno', $datos)->render();

        $pdf = SnappyPdf::loadHTML($html);
        $pdf->setPaper('letter')
            ->setOrientation('portrait')
            ->setOption('margin-top', 10)
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)
            ->setOption('encoding', 'utf-8')
            ->setOption('enable-local-file-access', true)
            ->setOption('load-error-handling', 'ignore');

        $nombreArchivo = 'recibo_' . $recibo->numero_recibo . '_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        return $pdf->stream($nombreArchivo);
    }

    public function getReporteMontos(int $id): array
    {
        $recibo = $this->reciboRepository->find($id);
        if (!$recibo) {
            throw new Exception('Recibo no encontrado');
        }

        $detalles = $recibo->detalles;
        $formasPago = $recibo->formasPago;

        $totalDetalles = (float) $detalles->sum('total');
        $totalCantidad = (float) $detalles->sum('cantidad');
        $totalFormasPago = (float) $formasPago->sum('monto');

        $porTipoPago = $detalles->groupBy('tipo_pago')->map(function ($group) {
            return [
                'cantidad_items' => $group->count(),
                'total_montos' => (float) $group->sum('total')
            ];
        })->toArray();

        return [
            'recibo' => [
                'id' => $recibo->id,
                'numero_recibo' => $recibo->numero_recibo,
                'tipo' => $recibo->tipo,
                'estado' => $recibo->estado,
                'fecha' => optional($recibo->fecha)->format('Y-m-d'),
                'total' => (float) $recibo->total,
                'tasa_cambio' => (float) $recibo->tasa_cambio,
            ],
            'totales' => [
                'total_detalles' => $totalDetalles,
                'total_cantidad' => $totalCantidad,
                'total_formas_pago' => $totalFormasPago,
            ],
            'por_tipo_pago' => $porTipoPago,
            'detalles' => $detalles->map(function ($d) {
                return [
                    'concepto' => $d->concepto,
                    'cantidad' => (float) $d->cantidad,
                    'monto' => (float) $d->monto,
                    'total' => (float) $d->total,
                    'tipo_pago' => $d->tipo_pago,
                ];
            })->toArray(),
            'formas_pago' => $formasPago->map(function ($fp) {
                return [
                    'forma_pago_id' => $fp->forma_pago_id,
                    'monto' => (float) $fp->monto,
                ];
            })->toArray(),
        ];
    }

    /**
     * Generar reporte PDF del historial de recibos del usuario
     */
    public function generarPdfHistorial(int $userId, array $filters = [])
    {
        $user = \App\Models\User::findOrFail($userId);

        $query = Recibo::where('user_id', $userId)
            ->where('estado', '!=', 'anulado')
            ->with(['detalles']);

        if (!empty($filters['fecha_inicio'])) {
            $query->whereDate('fecha', '>=', $filters['fecha_inicio']);
        }

        if (!empty($filters['fecha_fin'])) {
            $query->whereDate('fecha', '<=', $filters['fecha_fin']);
        }

        $recibos = $query->orderBy('fecha', 'asc')->orderBy('id', 'asc')->get();

        $datos = [
            'user' => $user,
            'recibos' => $recibos,
            'fecha_inicio' => $filters['fecha_inicio'] ?? null,
            'fecha_fin' => $filters['fecha_fin'] ?? null,
            'fecha_generacion' => now()->format('d/m/Y H:i:s'),
            'institucion' => config('app.nombre_institucion', 'MINISTERIO DE EDUCACIÓN')
        ];

        $html = view('pdf.historial-recibos', $datos)->render();

        $titulo = 'HISTORIAL DE RECIBOS';
        $subtitulo1 = 'Alumno: ' . $user->nombre_completo;
        $subtitulo2 = 'Período: ' . ($filters['fecha_inicio'] ?? 'Inicio') . ' al ' . ($filters['fecha_fin'] ?? 'Fin');
        $nombreInstitucion = $datos['institucion'];

        $headerHtml = view()->make('pdf.header', compact('titulo', 'subtitulo1', 'subtitulo2', 'nombreInstitucion'))->render();

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

        $nombreArchivo = 'historial_recibos_' . str_replace(' ', '_', $user->name) . '_' . now()->format('Ymd_His') . '.pdf';

        return $pdf->stream($nombreArchivo);
    }
}
