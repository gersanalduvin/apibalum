<?php

namespace App\Jobs;

use App\Models\User;
use App\Mail\CredencialesFamiliaMailable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendCredencialesFamiliaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $familia;
    protected $passwordPlano;
    protected $hijos;

    /**
     * Create a new job instance.
     */
    public function __construct(User $familia, string $passwordPlano, $hijos = [])
    {
        $this->familia = $familia;
        $this->passwordPlano = $passwordPlano;
        $this->hijos = $hijos;
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Mail::send(new CredencialesFamiliaMailable(
                $this->familia,
                $this->passwordPlano,
                $this->hijos
            ));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("EXCEPTION IN SEND JOB: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        }
    }
}
