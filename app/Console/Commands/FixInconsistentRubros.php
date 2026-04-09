<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\UsersAranceles;

class FixInconsistentRubros extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rubros:fix-inconsistencies {--dry-run : Solo mostrar lo que se cambiaría}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detecta y corrige rubros que están marcados como pendientes pero tienen recibos activos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $this->info($dryRun ? "--- MODO SIMULACIÓN (DRY RUN) ---" : "--- INICIANDO CORRECCIÓN DE RUBROS ---");

        $pendientes = DB::table('users_aranceles as ua')
            ->join('users as u', 'ua.user_id', '=', 'u.id')
            ->leftJoin('config_plan_pago_detalle as cppd', 'ua.rubro_id', '=', 'cppd.id')
            ->where(function($q) {
                $q->where('ua.estado', '!=', 'pagado')
                  ->orWhere('ua.saldo_actual', '>', 0);
            })
            ->whereNull('ua.deleted_at')
            ->select(
                'u.id as user_id',
                'u.primer_nombre',
                'u.primer_apellido',
                'ua.id as ua_id',
                'ua.rubro_id',
                'ua.importe_total',
                'ua.saldo_actual',
                'ua.estado as ua_estado',
                'cppd.nombre as rubro_nombre',
                'cppd.asociar_mes'
            )
            ->get();

        $encontrados = 0;
        $corregidos = 0;

        foreach ($pendientes as $p) {
            if (!$p->rubro_nombre) continue;

            $nombre = strtoupper($p->rubro_nombre);
            $mes = strtoupper($p->asociar_mes ?? '');
            
            // Extraer año para mayor precisión
            $anioRubro = null;
            if (preg_match('/(\d{4})/', $nombre, $matches)) {
                $anioRubro = $matches[1];
            }

            $recibosMatches = DB::table('recibos_detalle as rd')
                ->join('recibos as r', 'rd.recibo_id', '=', 'r.id')
                ->where('r.user_id', $p->user_id)
                ->where('r.estado', '!=', 'anulado')
                ->get()
                ->filter(function($r) use ($nombre, $mes, $anioRubro) {
                    $concepto = strtoupper($r->concepto);
                    
                    // Validar mes
                    $matchMes = $mes && strpos($concepto, $mes) !== false;
                    if (!$matchMes) return false;
                    
                    // Validar año si existe en el rubro
                    if ($anioRubro) {
                        return strpos($concepto, $anioRubro) !== false;
                    }
                    
                    return true;
                });

            if ($recibosMatches->isNotEmpty()) {
                $encontrados++;
                $this->warn("Inconsistencia detectada para Alumno ID {$p->user_id}: {$p->primer_nombre} {$p->primer_apellido}");
                $this->line("  Rubro: {$p->rubro_nombre} (UA_ID: {$p->ua_id}) | Saldo: {$p->saldo_actual}");
                
                foreach ($recibosMatches as $rm) {
                    $this->line("  Pagado en Recibo: {$rm->numero_recibo} | Fecha: {$rm->fecha} | Total: {$rm->total}");
                }

                if (!$dryRun) {
                    UsersAranceles::where('id', $p->ua_id)->update([
                        'saldo_pagado' => $p->importe_total,
                        'saldo_actual' => 0,
                        'estado' => 'pagado',
                        'updated_by' => 1 // Sistema/Admin
                    ]);
                    $this->info("  [CORREGIDO]");
                    $corregidos++;
                }
                $this->line("--------------------------------------------------");
            }
        }

        $this->info("\nResumen:");
        $this->info("Total inconsistencias encontradas: $encontrados");
        if (!$dryRun) {
            $this->info("Total registros corregidos: $corregidos");
        }
    }
}
