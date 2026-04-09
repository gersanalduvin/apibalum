<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Console\Commands\DispatchTestEmailJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('queue:test-email {to} {--type=simple} {--subject="Prueba de cola"} {--body="Hola desde la cola"} {--template=} {--delay=0} {--from=}', function ($to, $type = 'simple', $subject = 'Prueba de cola', $body = 'Hola desde la cola', $template = null, $delay = 0, $from = null) {
    $this->info('Despachando email de prueba...');

    $command = new DispatchTestEmailJob();
    // Simular ejecución del comando programático
    $command->setLaravel(app());
    $this->comment("Use 'php artisan app:dispatch-test-email' para control total.");
    // Redirigir a comando dedicado
    \Artisan::call('app:dispatch-test-email', [
        'to' => $to,
        '--type' => $type,
        '--subject' => $subject,
        '--body' => $body,
        '--template' => $template,
        '--delay' => $delay,
        '--from' => $from,
    ]);
    $this->info('Comando ejecutado. Revise logs para el resultado.');
})->purpose('Despacha un SendEmailJob de prueba rápidamente');
