<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessDataJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * The maximum number of seconds the job can run.
     *
     * @var int
     */
    public $timeout = 300; // 5 minutos

    protected $taskType;
    protected $taskData;
    protected $priority;

    /**
     * Create a new job instance.
     */
    public function __construct(string $taskType, array $taskData, string $priority = 'normal')
    {
        $this->taskType = $taskType;
        $this->taskData = $taskData;
        $this->priority = $priority;
        
        // Asignar a cola según prioridad
        $queueName = match($priority) {
            'high' => env('QUEUE_HIGH_PRIORITY', 'high'),
            'low' => env('QUEUE_LOW_PRIORITY', 'low'),
            default => env('QUEUE_PROCESSING', 'processing')
        };
        
        $this->onQueue($queueName);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Iniciando procesamiento de tarea', [
                'type' => $this->taskType,
                'priority' => $this->priority,
                'queue' => $this->queue
            ]);

            $startTime = microtime(true);

            switch ($this->taskType) {
                case 'image_processing':
                    $this->processImage();
                    break;
                    
                case 'data_export':
                    $this->exportData();
                    break;
                    
                case 'file_cleanup':
                    $this->cleanupFiles();
                    break;
                    
                case 'report_generation':
                    $this->generateReport();
                    break;
                    
                case 'notification_batch':
                    $this->sendBatchNotifications();
                    break;
                    
                default:
                    throw new \InvalidArgumentException("Tipo de tarea no soportado: {$this->taskType}");
            }

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('Tarea procesada exitosamente', [
                'type' => $this->taskType,
                'execution_time_ms' => $executionTime,
                'priority' => $this->priority
            ]);

        } catch (\Exception $e) {
            Log::error('Error al procesar tarea', [
                'type' => $this->taskType,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'priority' => $this->priority
            ]);

            throw $e;
        }
    }

    /**
     * Procesar imágenes
     */
    private function processImage(): void
    {
        $imagePath = $this->taskData['image_path'] ?? null;
        
        if (!$imagePath) {
            throw new \InvalidArgumentException('Ruta de imagen requerida');
        }

        // Simular procesamiento de imagen
        Log::info('Procesando imagen', ['path' => $imagePath]);
        
        // Aquí iría la lógica real de procesamiento de imagen
        sleep(2); // Simular trabajo
        
        Log::info('Imagen procesada', ['path' => $imagePath]);
    }

    /**
     * Exportar datos
     */
    private function exportData(): void
    {
        $format = $this->taskData['format'] ?? 'csv';
        $data = $this->taskData['data'] ?? [];
        
        Log::info('Exportando datos', ['format' => $format, 'records' => count($data)]);
        
        // Simular exportación
        sleep(3);
        
        Log::info('Datos exportados exitosamente');
    }

    /**
     * Limpiar archivos temporales
     */
    private function cleanupFiles(): void
    {
        $directory = $this->taskData['directory'] ?? 'temp';
        $olderThan = $this->taskData['older_than_days'] ?? 7;
        
        Log::info('Limpiando archivos', ['directory' => $directory, 'older_than_days' => $olderThan]);
        
        // Simular limpieza
        sleep(1);
        
        Log::info('Limpieza de archivos completada');
    }

    /**
     * Generar reportes
     */
    private function generateReport(): void
    {
        $reportType = $this->taskData['report_type'] ?? 'general';
        
        Log::info('Generando reporte', ['type' => $reportType]);
        
        // Simular generación de reporte
        sleep(5);
        
        Log::info('Reporte generado exitosamente');
    }

    /**
     * Enviar notificaciones en lote
     */
    private function sendBatchNotifications(): void
    {
        $notifications = $this->taskData['notifications'] ?? [];
        
        Log::info('Enviando notificaciones en lote', ['count' => count($notifications)]);
        
        foreach ($notifications as $notification) {
            // Simular envío de notificación
            usleep(100000); // 0.1 segundos por notificación
        }
        
        Log::info('Notificaciones enviadas exitosamente');
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessDataJob falló definitivamente', [
            'type' => $this->taskType,
            'error' => $exception->getMessage(),
            'priority' => $this->priority
        ]);
    }
}
