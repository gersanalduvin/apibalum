<?php

namespace App\Jobs;

use App\Services\SesService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
 

class SendEmailJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     *
     * @var int
     */
    public $timeout = 60;

    protected $emailData;
    protected $emailType;

    /**
     * Create a new job instance.
     */
    public function __construct(array $emailData, string $emailType = 'simple')
    {
        $this->emailData = $emailData;
        $this->emailType = $emailType;
        
        // Asignar a cola específica según el tipo
        $this->onQueue(env('QUEUE_EMAIL', 'emails'));
    }

    /**
     * Execute the job.
     */
    public function handle(SesService $sesService): void
    {
        try {
            

            if ($this->emailType === 'templated') {
                $data = [
                    'to' => $this->emailData['to'],
                    'template' => $this->emailData['template'],
                    // Aceptar tanto 'templateData' como 'template_data'
                    'template_data' => $this->emailData['templateData'] ?? ($this->emailData['template_data'] ?? []),
                    'from' => $this->emailData['from'] ?? null,
                    'cc' => $this->emailData['cc'] ?? [],
                    'bcc' => $this->emailData['bcc'] ?? [],
                    'reply_to' => $this->emailData['reply_to'] ?? [],
                ];
                $result = $sesService->sendTemplatedEmail($data);
            } else {
                $data = [
                    'to' => $this->emailData['to'],
                    'subject' => $this->emailData['subject'],
                    // Permitir usar 'html' o 'body' como contenido HTML
                    'html' => $this->emailData['html'] ?? ($this->emailData['body'] ?? ''),
                    'text' => $this->emailData['text'] ?? '',
                    'from' => $this->emailData['from'] ?? null,
                    'cc' => $this->emailData['cc'] ?? [],
                    'bcc' => $this->emailData['bcc'] ?? [],
                    'reply_to' => $this->emailData['reply_to'] ?? [],
                ];
                $result = $sesService->sendEmail($data);
            }
            

        } catch (\Exception $e) {
            // Si es el último intento, no volver a intentar
            if ($this->attempts() >= $this->tries) {
                // sin logging
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        // sin logging
    }
}
