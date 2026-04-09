<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Audit;
use Illuminate\Support\Str;

class AuditQuery extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:query {model : Nombre del modelo} {--limit=10 : Número de registros a mostrar} {--event= : Filtrar por evento específico} {--user= : Filtrar por usuario específico}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consultar auditorías de un modelo específico';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $modelName = $this->argument('model');
        $limit = $this->option('limit');
        $event = $this->option('event');
        $userId = $this->option('user');

        // Construir el nombre completo de la clase del modelo
        $modelClass = 'App\\Models\\' . Str::studly($modelName);

        // Verificar si el modelo existe
        if (!class_exists($modelClass)) {
            $this->error("El modelo {$modelName} no existe.");
            return 1;
        }

        // Construir la consulta
        $query = Audit::where('model_type', $modelClass);

        if ($event) {
            $query->where('event', $event);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $audits = $query->orderBy('created_at', 'desc')
                       ->limit($limit)
                       ->get();

        if ($audits->isEmpty()) {
            $this->info("No se encontraron auditorías para el modelo {$modelName}.");
            return 0;
        }

        // Mostrar los resultados
        $this->info("Auditorías para el modelo {$modelName}:");
        $this->line('');

        $headers = ['ID', 'Evento', 'Usuario', 'Fecha', 'Cambios'];
        $rows = [];

        foreach ($audits as $audit) {
            $changes = '';
            if ($audit->old_values || $audit->new_values) {
                $oldValues = $audit->old_values ? json_encode($audit->old_values, JSON_UNESCAPED_UNICODE) : '{}';
                $newValues = $audit->new_values ? json_encode($audit->new_values, JSON_UNESCAPED_UNICODE) : '{}';
                $changes = "Anterior: {$oldValues}\nNuevo: {$newValues}";
            }

            $rows[] = [
                $audit->id,
                $audit->event,
                $audit->user_id ?? 'Sistema',
                $audit->created_at->format('Y-m-d H:i:s'),
                Str::limit($changes, 50)
            ];
        }

        $this->table($headers, $rows);

        // Mostrar estadísticas
        $this->line('');
        $this->info("Total de registros mostrados: {$audits->count()}");
        
        $totalAudits = Audit::where('model_type', $modelClass)->count();
        $this->info("Total de auditorías para este modelo: {$totalAudits}");

        // Mostrar eventos disponibles
        $events = Audit::where('auditable_type', $modelClass)
                      ->distinct()
                      ->pluck('event')
                      ->toArray();
        
        if (!empty($events)) {
            $this->line('');
            $this->info('Eventos disponibles: ' . implode(', ', $events));
        }

        return 0;
    }
}