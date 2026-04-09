<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Aviso;
use App\Models\User;
use App\Services\AvisoService;
use App\Http\Controllers\Api\V1\AvisoController;
use Illuminate\Http\Request;

class TestAvisoCountLogic extends Command
{
    protected $signature = 'test:aviso-count {userId}';
    protected $description = 'Verify unreadCount logic for a user';

    public function handle()
    {
        $userId = $this->argument('userId');
        $user = User::findOrFail($userId);

        $this->info("Testing Aviso Count Logic for User: {$user->name} (ID: $userId)");

        // 1. Get avisos from service
        $service = app(AvisoService::class);
        $avisos = $service->getAvisosForUser($user);

        $this->info("Total Avisos returned by service: " . count($avisos));

        // 2. Simulate Controller logic manually to trace
        $unreadCount = 0;
        $currentUserId = $user->id;

        foreach ($avisos as $aviso) {
            $avisoUserId = is_array($aviso) ? ($aviso['user_id'] ?? null) : ($aviso->user_id ?? null);

            // Log for debugging
            $this->line("Checking Aviso ID: " . (is_array($aviso) ? $aviso['id'] : $aviso->id));
            $this->line("  - Creator ID: $avisoUserId");

            if ((int)$avisoUserId === (int)$currentUserId) {
                $this->line("    [SKIPPED] User is the creator.");
                continue;
            }

            $isRead = is_array($aviso) ? ($aviso['leido_por_mi'] ?? false) : ($aviso->leido_por_mi ?? false);
            $this->line("  - Is Read (per system): " . ($isRead ? 'YES' : 'NO'));

            if (!$isRead) {
                $unreadCount++;
                $this->line("    [COUNTED] +1 unread");
            }
        }

        $this->info("FINAL UNREAD COUNT: $unreadCount");

        // 3. Verify against Controller method
        $controller = new AvisoController($service);
        \Illuminate\Support\Facades\Auth::login($user);
        $response = $controller->unreadCount(new Request());
        $data = $response->getData();

        $this->info("Controller unreadCount API returns: " . ($data->data->unread_count ?? 'ERROR'));

        if ($unreadCount == ($data->data->unread_count ?? -1)) {
            $this->info("✅ SUCCESS: Logic is consistent.");
        } else {
            $this->error("❌ FAILURE: Logic discrepancy found.");
        }
    }
}
