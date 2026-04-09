<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\ConfigPlanPago;
use App\Models\User;
use App\Models\Audit;
use Exception;

class TestAuditSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:test {--cleanup : Limpiar datos de prueba al finalizar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba el nuevo sistema de auditoría robusta';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Iniciando prueba del nuevo sistema de auditoría...');
        $this->newLine();

        try {
            // 1. Verificar que la tabla audits existe
            $this->info('1️⃣ Verificando tabla audits...');
            $auditCount = DB::table('audits')->count();
            $this->line("   ✅ Tabla audits existe con {$auditCount} registros");
            $this->newLine();

            // 2. Obtener un usuario para las pruebas
            $this->info('2️⃣ Obteniendo usuario para pruebas...');
            $user = User::first();
            if (!$user) {
                $this->error('   ❌ No hay usuarios en la base de datos');
                return 1;
            }
            $this->line("   ✅ Usuario encontrado: {$user->name} ({$user->email})");
            $this->newLine();

            // 3. Simular autenticación
            $this->info('3️⃣ Simulando autenticación...');
            auth()->login($user);
            $this->line('   ✅ Usuario autenticado');
            $this->newLine();

            // 4. Crear un nuevo ConfigPlanPago para probar auditoría de creación
            $this->info('4️⃣ Creando nuevo ConfigPlanPago...');
            $planPago = ConfigPlanPago::create([
                'nombre' => 'Plan de Prueba Auditoría - ' . now()->format('Y-m-d H:i:s'),
                'estado' => true,
                'periodo_lectivo_id' => 1, // Asumiendo que existe
            ]);
            $this->line("   ✅ ConfigPlanPago creado con ID: {$planPago->id}");

            // 5. Verificar auditorías de creación
            $this->info('5️⃣ Verificando auditorías de creación...');
            $createAudits = $planPago->audits()->where('event', 'created')->get();
            $this->line("   📊 Auditorías de creación encontradas: {$createAudits->count()}");
            
            if ($createAudits->count() > 0) {
                $audit = $createAudits->first();
                $this->line('   📝 Primera auditoría:');
                $this->line("      - Usuario: {$audit->user->name}");
                $this->line("      - Evento: {$audit->event}");
                $this->line("      - Tabla: {$audit->table_name}");
                $this->line("      - Fecha: {$audit->created_at}");
            }
            $this->newLine();

            // 6. Actualizar el ConfigPlanPago para probar auditoría de actualización
            $this->info('6️⃣ Actualizando ConfigPlanPago...');
            $planPago->update([
                'nombre' => 'Plan de Prueba Auditoría ACTUALIZADO - ' . now()->format('Y-m-d H:i:s'),
                'estado' => false,
            ]);
            $this->line('   ✅ ConfigPlanPago actualizado');

            // 7. Verificar auditorías de actualización
            $this->info('7️⃣ Verificando auditorías de actualización...');
            $updateAudits = $planPago->audits()->where('event', 'updated')->get();
            $this->line("   📊 Auditorías de actualización encontradas: {$updateAudits->count()}");
            
            if ($updateAudits->count() > 0) {
                foreach ($updateAudits as $audit) {
                    if ($audit->column_name) {
                        $this->line('   📝 Cambio granular:');
                        $this->line("      - Campo: {$audit->column_name}");
                        $this->line("      - Valor anterior: {$audit->old_value}");
                        $this->line("      - Valor nuevo: {$audit->new_value}");
                        $this->line("      - Usuario: {$audit->user->name}");
                        $this->line("      - Fecha: {$audit->created_at}");
                    }
                }
            }
            $this->newLine();

            // 8. Probar métodos del trait Auditable
            $this->info('8️⃣ Probando métodos del trait Auditable...');
            $recentAudits = $planPago->getRecentAudits(10);
            $this->line("   📊 Auditorías recientes: {$recentAudits->count()}");
            
            $fieldHistory = $planPago->getFieldHistory('nombre');
            $this->line("   📊 Historial del campo 'nombre': {$fieldHistory->count()}");
            $this->newLine();

            // 9. Eliminar el ConfigPlanPago para probar auditoría de eliminación
            $this->info('9️⃣ Eliminando ConfigPlanPago...');
            $planPago->delete();
            $this->line('   ✅ ConfigPlanPago eliminado (soft delete)');

            // 10. Verificar auditorías de eliminación
            $this->info('🔟 Verificando auditorías de eliminación...');
            $deleteAudits = Audit::where('model_type', ConfigPlanPago::class)
                                 ->where('model_id', $planPago->id)
                                 ->where('event', 'deleted')
                                 ->get();
            $this->line("   📊 Auditorías de eliminación encontradas: {$deleteAudits->count()}");
            $this->newLine();

            // 11. Resumen final
            $this->info('📋 RESUMEN FINAL:');
            $totalAudits = Audit::where('model_type', ConfigPlanPago::class)
                                ->where('model_id', $planPago->id)
                                ->count();
            $this->line("   📊 Total de auditorías creadas para este registro: {$totalAudits}");
            
            $auditsByEvent = Audit::where('model_type', ConfigPlanPago::class)
                                  ->where('model_id', $planPago->id)
                                  ->selectRaw('event, COUNT(*) as count')
                                  ->groupBy('event')
                                  ->get();
            
            foreach ($auditsByEvent as $eventCount) {
                $this->line("   📈 {$eventCount->event}: {$eventCount->count} auditorías");
            }

            $this->newLine();
            $this->info('🎉 ¡Prueba del nuevo sistema de auditoría completada exitosamente!');
            $this->info('💡 El sistema está registrando correctamente todos los eventos de auditoría.');

            // Limpiar datos de prueba si se solicita
            if ($this->option('cleanup')) {
                $this->newLine();
                $this->info('🧹 Limpiando datos de prueba...');
                
                // Eliminar auditorías de prueba
                Audit::where('model_type', ConfigPlanPago::class)
                     ->where('model_id', $planPago->id)
                     ->delete();
                
                // Forzar eliminación del registro de prueba
                $planPago->forceDelete();
                
                $this->line('   ✅ Datos de prueba eliminados');
            }

            return 0;

        } catch (Exception $e) {
            $this->error("❌ Error durante la prueba: " . $e->getMessage());
            $this->error("📍 Archivo: " . $e->getFile() . " línea " . $e->getLine());
            return 1;
        }
    }
}
