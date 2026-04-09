<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SendEmailJob;

class DispatchTestEmailJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:dispatch-test-email {to} {--type=simple} {--subject="Prueba de cola"} {--body="Hola desde la cola"} {--template=} {--delay=0} {--from=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Despacha un SendEmailJob de prueba a la cola de emails';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $to = $this->argument('to');
        $type = $this->option('type');
        $delay = (int) $this->option('delay');
        $from = $this->option('from');

        $emailData = [
            'to' => $to,
        ];

        if ($from) {
            $emailData['from'] = $from;
        }

        if ($type === 'templated') {
            $template = $this->option('template');
            if (!$template) {
                $this->error('Debe proporcionar --template para type=templated');
                return self::FAILURE;
            }
            $emailData['template'] = $template;
            $emailData['template_data'] = ['name' => 'Usuario Demo'];
        } else {
            $emailData['subject'] = $this->option('subject');
            $emailData['html'] = $this->option('body');
        }

        $job = new SendEmailJob($emailData, $type);

        if ($delay > 0) {
            $job->delay(now()->addSeconds($delay));
        }

        dispatch($job);

        $this->info('Job de email despachado');
        $this->line('Queue: ' . env('QUEUE_EMAIL', 'emails'));
        $this->line('Tipo: ' . $type);

        return self::SUCCESS;
    }
}