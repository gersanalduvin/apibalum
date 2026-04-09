<?php

namespace App\Services;

use App\Repositories\ConfigArqueoDetalleRepository;
use App\Repositories\ConfigArqueoMonedaRepository;
use App\Repositories\ConfigArqueoRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Exception;

class ArqueoCajaService
{
    public function __construct(
        private ConfigArqueoRepository $arqueoRepo,
        private ConfigArqueoDetalleRepository $detalleRepo,
        private ConfigArqueoMonedaRepository $monedaRepo
    ) {}

    public function resumenFormasPago(?string $fecha = null, ?string $desde = null, ?string $hasta = null): array
    {
        $query = DB::table('recibos_forma_pago as rfp')
            ->join('recibos as r', 'rfp.recibo_id', '=', 'r.id')
            ->join('config_formas_pago as fp', 'rfp.forma_pago_id', '=', 'fp.id')
            ->where('r.estado', 'activo')
            ->whereNull('r.deleted_at')
            ->whereNull('rfp.deleted_at')
            ->selectRaw('rfp.forma_pago_id, fp.nombre, SUM(rfp.monto) as total')
            ->groupBy('rfp.forma_pago_id', 'fp.nombre');

        if ($fecha) {
            $query->whereDate('r.fecha', '=', $fecha);
        } else {
            if ($desde) {
                $query->whereDate('r.fecha', '>=', $desde);
            }
            if ($hasta) {
                $query->whereDate('r.fecha', '<=', $hasta);
            }
        }

        $detalles = $query->orderBy('fp.nombre')->get();
        $totalGeneral = $detalles->sum('total');

        $savedArqueo = null;
        if ($fecha) {
            $existing = $this->arqueoRepo->findByDate($fecha);
            if ($existing) {
                $savedDetalles = $existing->detalles->map(function ($d) {
                    $den = $d->moneda ? $d->moneda->denominacion : '';
                    $mult = $d->moneda ? (float)$d->moneda->multiplicador : 0.0;
                    $isDolar = $d->moneda ? (bool)$d->moneda->moneda : false;
                    return [
                        'moneda_id' => (int)$d->moneda_id,
                        'denominacion' => $den,
                        'multiplicador' => $mult,
                        'cantidad' => (float)$d->cantidad,
                        'total' => (float)$d->total,
                        'es_dolar' => $isDolar,
                    ];
                })->toArray();

                $savedArqueo = [
                    'id' => $existing->id,
                    'fecha' => $existing->fecha ? $existing->fecha->format('Y-m-d') : null,
                    'tasacambio' => (float)$existing->tasacambio,
                    'totalc' => (float)$existing->totalc,
                    'totald' => (float)$existing->totald,
                    'totalarqueo' => (float)$existing->totalarqueo,
                    'detalles' => $savedDetalles,
                ];
            }
        }

        return [
            'detalles' => $detalles,
            'total_general' => $totalGeneral,
            'saved_arqueo' => $savedArqueo
        ];
    }

    public function obtenerMonedasSeparadas(): array
    {
        $cordoba = $this->monedaRepo->getAllByMoneda(false);
        $dolar = $this->monedaRepo->getAllByMoneda(true);
        return [
            'cordoba' => $cordoba,
            'dolar' => $dolar
        ];
    }

    public function guardarArqueoConDetalles(array $data): array
    {
        try {
            DB::beginTransaction();

            $fecha = $data['fecha'];
            $tasaCambio = (float) $data['tasacambio'];
            $detallesInput = $data['detalles'] ?? [];

            $totalC = 0.0;
            $totalD = 0.0;

            // Buscar si ya existe un arqueo para esta fecha
            $arqueo = $this->arqueoRepo->findByDate($fecha);

            if ($arqueo) {
                // Actualizar cabecera
                $this->arqueoRepo->update($arqueo->id, [
                    'tasacambio' => $tasaCambio,
                    'updated_by' => Auth::id(),
                    'version' => $arqueo->version + 1
                ]);

                // Eliminar detalles anteriores
                $this->detalleRepo->deleteByArqueoId($arqueo->id);
            } else {
                // Crear nuevo
                $arqueo = $this->arqueoRepo->create([
                    'fecha' => $fecha,
                    'totalc' => 0,
                    'totald' => 0,
                    'tasacambio' => $tasaCambio,
                    'totalarqueo' => 0,
                    'created_by' => Auth::id(),
                    'version' => 1
                ]);
            }

            foreach ($detallesInput as $item) {
                $monedaId = (int) $item['moneda_id'];
                $cantidad = (float) $item['cantidad'];
                $moneda = $this->monedaRepo->find($monedaId);
                if (!$moneda) {
                    throw new Exception('Moneda no encontrada');
                }
                $total = $cantidad * (float) $moneda->multiplicador;

                if ($moneda->moneda) {
                    $totalD += $total;
                } else {
                    $totalC += $total;
                }

                $this->detalleRepo->create([
                    'arqueo_id' => $arqueo->id,
                    'moneda_id' => $monedaId,
                    'cantidad' => $cantidad,
                    'total' => $total,
                    'created_by' => Auth::id(),
                    'version' => 1
                ]);
            }

            $totalArqueo = $totalC + ($totalD * $tasaCambio);
            $this->arqueoRepo->update($arqueo->id, [
                'totalc' => $totalC,
                'totald' => $totalD,
                'totalarqueo' => $totalArqueo,
                'updated_by' => Auth::id()
            ]);

            DB::commit();

            $result = $this->arqueoRepo->find($arqueo->id);
            return ['arqueo' => $result];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function obtenerArqueoConDetalles(int $id): array
    {
        $m = $this->arqueoRepo->find($id);
        if (!$m) {
            throw new Exception('Arqueo no encontrado');
        }
        $detalles = $m->detalles->map(function ($d) {
            $den = $d->moneda ? $d->moneda->denominacion : '';
            $mult = $d->moneda ? (float)$d->moneda->multiplicador : 0.0;
            $isDolar = $d->moneda ? (bool)$d->moneda->moneda : false;
            return [
                'moneda_id' => (int)$d->moneda_id,
                'denominacion' => $den,
                'multiplicador' => $mult,
                'cantidad' => (float)$d->cantidad,
                'total' => (float)$d->total,
                'es_dolar' => $isDolar,
            ];
        })->toArray();

        return [
            'id' => $m->id,
            'fecha' => $m->fecha ? $m->fecha->format('Y-m-d') : null,
            'tasacambio' => (float)$m->tasacambio,
            'totalc' => (float)$m->totalc,
            'totald' => (float)$m->totald,
            'totalarqueo' => (float)$m->totalarqueo,
            'detalles' => $detalles,
        ];
    }

    public function generarPdfArqueo(int $id)
    {
        $data = $this->obtenerArqueoConDetalles($id);
        $resumen = $this->resumenFormasPago($data['fecha'], null, null);

        $tasacambio = $data['tasacambio'];
        $totalEfectivo = 0;
        foreach (($resumen['detalles'] ?? []) as $detalle) {
            $nombre = strtoupper($detalle->nombre ?? '');
            $monto = (float)($detalle->total ?? 0);

            if (str_contains($nombre, 'EFECTIVO')) {
                // Priorizar detección de Córdobas
                if (str_contains($nombre, 'C$') || str_contains($nombre, 'CÓRDOBA') || str_contains($nombre, 'CORDOBA')) {
                    $totalEfectivo += $monto;
                }
                // Detectar Dólares
                elseif (str_contains($nombre, '$') || str_contains($nombre, 'DOLAR') || str_contains($nombre, 'DÓLAR') || str_contains($nombre, 'US')) {
                    $totalEfectivo += ($monto * $tasacambio);
                }
                // Default
                else {
                    $totalEfectivo += $monto;
                }
            }
        }

        $html = view('pdf.arqueo-caja-detalles', [
            'data' => $data,
            'resumenFormasPago' => $resumen,
            'totalEfectivo' => $totalEfectivo
        ])->render();

        /*
         * Se elimina el uso de header-html por problemas de compatibilidad en algunos entornos.
         * El encabezado se ha movido directamente a la vista (body).
         */

        $pdf = SnappyPdf::loadHTML($html)
            ->setPaper('letter')
            ->setOrientation('portrait')
            ->setOption('margin-top', 10)
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)
            ->setOption('enable-local-file-access', true)
            ->setOption('footer-center', 'Página [page] de [toPage]')
            ->setOption('footer-font-size', 8)
            ->setOption('load-error-handling', 'ignore');

        $nombreArchivo = 'arqueo_caja_detalles_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        return $pdf->download($nombreArchivo);
    }
}
