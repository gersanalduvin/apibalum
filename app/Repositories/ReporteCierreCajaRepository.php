<?php

namespace App\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReporteCierreCajaRepository
{
    public function getDetalles(string $tipo, string $fechaInicio, string $fechaFin): Collection
    {
        $query = DB::table('recibos')
            ->join('recibos_detalle', 'recibos_detalle.recibo_id', '=', 'recibos.id')
            ->select([
                'recibos.fecha',
                'recibos.numero_recibo',
                'recibos.tipo',
                'recibos.estado',
                'recibos.nombre_usuario',
                'recibos_detalle.concepto',
                'recibos_detalle.total',
            ])
            ->whereBetween('recibos.fecha', [$fechaInicio, $fechaFin])
            ->whereNull('recibos.deleted_at')
            ->whereNull('recibos_detalle.deleted_at');

        if ($tipo !== 'todos') {
            $query->where('recibos.tipo', $tipo);
        }

        // Orden requerido: fecha, tipo y número de recibo
        return collect(
            $query
                ->orderBy('recibos.fecha', 'asc')
                ->orderBy('recibos.tipo', 'asc')
                ->orderBy('recibos.numero_recibo', 'asc')
                ->get()
        );
    }

    public function getDetallesPorConcepto(string $tipo, string $fechaInicio, string $fechaFin): Collection
    {
        $query = DB::table('recibos')
            ->join('recibos_detalle', 'recibos_detalle.recibo_id', '=', 'recibos.id')
            ->select([
                'recibos_detalle.concepto as concepto',
                DB::raw('COUNT(recibos_detalle.id) as count'),
                DB::raw('SUM(recibos_detalle.total) as sum_total'),
            ])
            ->whereBetween('recibos.fecha', [$fechaInicio, $fechaFin])
            ->whereNull('recibos.deleted_at')
            ->whereNull('recibos_detalle.deleted_at')
            ->groupBy('recibos_detalle.concepto');

        if ($tipo !== 'todos') {
            $query->where('recibos.tipo', $tipo);
        }

        return collect($query->orderBy('concepto', 'asc')->get());
    }
}
