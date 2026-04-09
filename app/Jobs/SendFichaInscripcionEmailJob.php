<?php

namespace App\Jobs;

use App\Services\UsersGrupoService;
use App\Services\SesService;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
 

class SendFichaInscripcionEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número de intentos
     */
    public int $tries = 3;

    /**
     * Timeout en segundos
     */
    public int $timeout = 120;

    protected int $usersGrupoId;
    protected string $recipientEmail;
    protected ?string $fromEmail;
    protected ?string $ccEmail;
    protected ?string $bccEmail;

    /**
     * Crear nueva instancia del Job
     */
    public function __construct(int $usersGrupoId, string $recipientEmail, ?string $fromEmail = null, ?string $ccEmail = null, ?string $bccEmail = null)
    {
        $this->usersGrupoId = $usersGrupoId;
        $this->recipientEmail = $recipientEmail;
        $this->fromEmail = $fromEmail;
        $this->ccEmail = $ccEmail;
        $this->bccEmail = $bccEmail;

        // Cola específica para emails
        $this->onQueue(env('QUEUE_EMAIL', 'emails'));
    }

    /**
     * Ejecutar el Job
     */
    public function handle(UsersGrupoService $usersGrupoService, SesService $sesService): void
    {
        

        // 1) Obtener datos completos del UsersGrupo y alumno
        $usersGrupo = $usersGrupoService->getUsersGrupoById($this->usersGrupoId);
        $alumno = $usersGrupo->user;

        // 2) Generar encabezado y pie de página
        $titulo = 'FICHA DE INSCRIPCIÓN - AÑO ESCOLAR ' . ($usersGrupo->periodoLectivo->nombre ?? '');
        $subtitulo1 = $usersGrupo->tipo_ingreso === 'nuevo_ingreso' ? 'NUEVO INGRESO' : 'REINGRESO';
        $nombreInstitucion = config('app.nombre_institucion', 'MINISTERIO DE EDUCACIÓN');
        $headerHtml = view()->make('pdf.header', compact('alumno', 'titulo', 'subtitulo1', 'nombreInstitucion'))->render();
        // Footer nativo de wkhtmltopdf
        $footerLeft = 'Fecha y hora: [date] [time]';
        $footerRight = 'Página [page] de [toPage]';

        // 3) Renderizar PDF desde la vista adecuada
        $pdf = SnappyPdf::loadView($usersGrupo->tipo_ingreso === 'nuevo_ingreso' ? 'pdf.ficha-inscripcion' : 'pdf.ficha-inscripcion-reingreso', compact('alumno'))
            ->setPaper('letter')
            ->setOrientation('portrait')
            ->setOption('margin-top', 35)
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 25)
            ->setOption('margin-left', 10)
            ->setOption('encoding', 'utf-8')
            ->setOption('enable-local-file-access', true)
            ->setOption('header-html', $headerHtml)
            ->setOption('header-spacing', 5)
            ->setOption('footer-left', $footerLeft)
            ->setOption('footer-right', $footerRight)
            ->setOption('footer-spacing', 5)
            ->setOption('load-error-handling', 'ignore');

        $pdfBytes = $pdf->output();

        // 4) Enviar email vía SES con PDF adjunto
        $codigoUnico = $alumno->codigo_unico ?? ('alumno_' . $alumno->id);
        $fileName = 'ficha_inscripcion_' . $codigoUnico . '_' . date('Y-m-d_H-i-s') . '.pdf';

        $subject = 'Ficha de inscripción - ' . ($alumno->primer_nombre . ' ' . $alumno->primer_apellido);
        $htmlBody = view('emails.ficha-inscripcion-adjunto', [
            'alumno' => $alumno,
        ])->render();

        $emailData = [
            'to' => $this->recipientEmail,
            'subject' => $subject,
            'html' => $htmlBody,
            'text' => "Hola,\n\nSe adjunta la ficha de inscripción en formato PDF.\n\nSaludos",
            'attachment_name' => $fileName,
            'attachment_bytes' => $pdfBytes,
            'content_type' => 'application/pdf',
        ];

        if ($this->fromEmail) { $emailData['from'] = $this->fromEmail; }
        if ($this->ccEmail) { $emailData['cc'] = [$this->ccEmail]; }
        if ($this->bccEmail) { $emailData['bcc'] = [$this->bccEmail]; }

        $result = $sesService->sendEmailWithAttachment($emailData);

        if (!$result['success']) {
            throw new \RuntimeException('Error al enviar el correo: ' . ($result['error'] ?? 'unknown'));
        }
    }

    /**
     * Manejar fallo definitivo del Job
     */
    public function failed(\Throwable $exception): void
    {
        // sin logging
    }
}