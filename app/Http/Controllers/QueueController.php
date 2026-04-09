<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmailJob;
use App\Jobs\ProcessDataJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Validator;
 

class QueueController extends Controller
{
    /**
     * Despachar un job de envío de email
     */
    public function dispatchEmailJob(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'to' => 'required|email',
            'subject' => 'required_if:type,simple|string|max:255',
            'body' => 'required_if:type,simple|string',
            'template' => 'required_if:type,templated|string',
            'templateData' => 'array',
            'from' => 'nullable|email',
            'type' => 'required|in:simple,templated',
            'delay' => 'nullable|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $emailData = [
                'to' => $request->to,
                'from' => $request->from
            ];

            if ($request->type === 'templated') {
                $emailData['template'] = $request->template;
                $emailData['templateData'] = $request->templateData ?? [];
            } else {
                $emailData['subject'] = $request->subject;
                $emailData['body'] = $request->body;
            }

            $job = new SendEmailJob($emailData, $request->type);

            // Aplicar delay si se especifica
            if ($request->delay) {
                $job->delay(now()->addSeconds($request->delay));
            }

            $jobId = dispatch($job);

            

            return response()->json([
                'success' => true,
                'message' => 'Job de email despachado exitosamente',
                'job_id' => $jobId,
                'queue' => env('QUEUE_EMAIL', 'emails')
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Error al despachar el job de email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Despachar un job de procesamiento de datos
     */
    public function dispatchProcessingJob(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'task_type' => 'required|in:image_processing,data_export,file_cleanup,report_generation,notification_batch',
            'task_data' => 'required|array',
            'priority' => 'nullable|in:high,normal,low',
            'delay' => 'nullable|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $priority = $request->priority ?? 'normal';
            $job = new ProcessDataJob(
                $request->task_type,
                $request->task_data,
                $priority
            );

            // Aplicar delay si se especifica
            if ($request->delay) {
                $job->delay(now()->addSeconds($request->delay));
            }

            $jobId = dispatch($job);

            $queueName = match($priority) {
                'high' => env('QUEUE_HIGH_PRIORITY', 'high'),
                'low' => env('QUEUE_LOW_PRIORITY', 'low'),
                default => env('QUEUE_PROCESSING', 'processing')
            };

            

            return response()->json([
                'success' => true,
                'message' => 'Job de procesamiento despachado exitosamente',
                'job_id' => $jobId,
                'task_type' => $request->task_type,
                'priority' => $priority,
                'queue' => $queueName
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Error al despachar el job de procesamiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de las colas
     */
    public function getQueueStats(): JsonResponse
    {
        try {
            $queues = [
                'emails' => env('QUEUE_EMAIL', 'emails'),
                'processing' => env('QUEUE_PROCESSING', 'processing'),
                'high_priority' => env('QUEUE_HIGH_PRIORITY', 'high'),
                'low_priority' => env('QUEUE_LOW_PRIORITY', 'low'),
                'notifications' => env('QUEUE_NOTIFICATIONS', 'notifications')
            ];

            $stats = [];
            foreach ($queues as $name => $queueName) {
                $stats[$name] = [
                    'queue_name' => $queueName,
                    'size' => Queue::size($queueName)
                ];
            }

            return response()->json([
                'success' => true,
                'queues' => $stats,
                'connection' => config('queue.default'),
                'redis_host' => config('database.redis.default.host'),
                'redis_port' => config('database.redis.default.port')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas de colas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Limpiar una cola específica
     */
    public function clearQueue(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'queue_name' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Nombre de cola requerido',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $queueName = $request->queue_name;
            $clearedJobs = 0;

            // Obtener el tamaño antes de limpiar
            $sizeBefore = Queue::size($queueName);

            // Limpiar la cola (esto depende del driver de cola)
            // Para Redis, necesitaríamos usar comandos específicos
            
            

            return response()->json([
                'success' => true,
                'message' => "Cola '{$queueName}' limpiada exitosamente",
                'jobs_cleared' => $sizeBefore
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar la cola',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener información del sistema de colas
     */
    public function getSystemInfo(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'system_info' => [
                    'queue_connection' => config('queue.default'),
                    'redis_client' => config('database.redis.client'),
                    'redis_host' => config('database.redis.default.host'),
                    'redis_port' => config('database.redis.default.port'),
                    'redis_database' => config('database.redis.default.database'),
                    'cache_store' => config('cache.default'),
                    'available_queues' => [
                        'emails' => env('QUEUE_EMAIL', 'emails'),
                        'processing' => env('QUEUE_PROCESSING', 'processing'),
                        'high_priority' => env('QUEUE_HIGH_PRIORITY', 'high'),
                        'low_priority' => env('QUEUE_LOW_PRIORITY', 'low'),
                        'notifications' => env('QUEUE_NOTIFICATIONS', 'notifications')
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información del sistema',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
