<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ConfigPlanPagoDetalle;
use Illuminate\Support\Facades\DB;

class ActualizarFechasRecargo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recargos:actualizar-config {--year= : Año a aplicar (por defecto el año actual)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza la fecha de vencimiento a los días 08 de cada mes y establece un recargo fijo de C$ 295 para mensualidades';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $year = $this->option('year') ?: date('Y');
        
        $mesesMap = [
            'enero' => '01',
            'febrero' => '02',
            'marzo' => '03',
            'abril' => '04',
            'mayo' => '05',
            'junio' => '06',
            'julio' => '07',
            'agosto' => '08',
            'septiembre' => '09',
            'octubre' => '10',
            'noviembre' => '11',
            'diciembre' => '12'
        ];

        $this->info("Iniciando actualización de configuración de recargos para mensualidades (Año {$year})...");

        // Obtenemos los rubros asociados a los meses (las colegiaturas/mensualidades)
        $detalles = ConfigPlanPagoDetalle::whereNotNull('asociar_mes')->get();

        $actualizados = 0;

        DB::beginTransaction();
        try {
            foreach ($detalles as $detalle) {
                $mesTexto = strtolower(trim($detalle->asociar_mes));
                
                if (isset($mesesMap[$mesTexto])) {
                    $fechaVencimiento = "{$year}-{$mesesMap[$mesTexto]}-08";
                    
                    // Actualizamos la configuración
                    $detalle->fecha_vencimiento = $fechaVencimiento;
                    $detalle->importe_recargo = 295.00;
                    $detalle->tipo_recargo = 'fijo'; // Fijo
                    // Opcional: asegurarnos de que quede en córdobas (moneda = false / 0)
                    $detalle->moneda = false; 
                    
                    // Actualizar silenciosamente si no queremos desencadenar otros mutators, pero save() está bien.
                    $detalle->save();

                    $actualizados++;
                }
            }
            DB::commit();
            $this->info("¡Completado exitosamente! Se actualizaron {$actualizados} rubros (mensualidades).");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Hubo un error durante la actualización: " . $e->getMessage());
        }
    }
}
