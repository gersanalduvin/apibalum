<?php

use App\Models\User;
use Illuminate\Support\Facades\Log;

// Fetch teachers
$teachers = User::where('tipo_usuario', 'docente')->get();

echo "Found " . $teachers->count() . " teachers.\n";

foreach ($teachers as $teacher) {
    try {
        $json = $teacher->toJson();
        echo "Teacher ID {$teacher->id}: OK\n";
    } catch (\Throwable $e) {
        echo "Teacher ID {$teacher->id}: ERROR - " . $e->getMessage() . "\n";
    }
}
