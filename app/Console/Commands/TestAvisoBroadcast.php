<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Aviso;
use App\Models\User;
use App\Events\AvisoCreado;

class TestAvisoBroadcast extends Command
{
    protected $signature = 'test:aviso-broadcast {userId} {avisoId?}';
    protected $description = 'Test AvisoCreado broadcast';

    public function handle()
    {
        $userId = $this->argument('userId');
        $avisoId = $this->argument('avisoId');

        // Simulate login as User 1 (usually the admin)
        \Illuminate\Support\Facades\Auth::loginUsingId(1);

        $user = User::findOrFail($userId);

        if ($avisoId) {
            $aviso = Aviso::findOrFail($avisoId);
        } else {
            $aviso = Aviso::whereHas('destinatarios', function ($q) {
                $q->where('para_todos', true);
            })->first();
        }

        if (!$aviso) {
            $this->error('No suitable avisos found in database.');
            return;
        }

        $this->info("Triggering AvisoCreado for Aviso ID: {$aviso->id}");
        $this->info("The logic should broadcast to all users except ID: 1");

        event(new AvisoCreado($aviso));

        $this->info('Event dispatched. Check laravel.log for "🔔 Aviso Broadcasting"');
    }
}
