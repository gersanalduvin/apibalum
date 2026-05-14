<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AplicarRecargosAranceles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aranceles:aplicar-recargos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aplica recargos a los aranceles pendientes cuya fecha de vencimiento haya pasado';

    /**
     * Execute the console command.
     */
    public function handle(\App\Services\UsersArancelesService $usersArancelesService)
    {
        $this->info('Iniciando proceso de aplicación de recargos...');

        try {
            $resultado = $usersArancelesService->aplicarRecargosVencidos();
            $this->info("Proceso finalizado con éxito.");
            $this->info("Total pendientes evaluados: " . $resultado['total_pendientes_encontrados']);
            $this->info("Recargos aplicados: " . $resultado['procesados']);
            if ($resultado['errores'] > 0) {
                $this->error("Errores encontrados: " . $resultado['errores']);
            }
        } catch (\Exception $e) {
            $this->error("Error al aplicar recargos: " . $e->getMessage());
        }
    }
}
