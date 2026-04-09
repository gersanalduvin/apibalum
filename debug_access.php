<?php

use App\Models\User;
use App\Models\UsersFamilia;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Starting Access Debug Script...\n";

    $studentId = 142;
    $parentEmail = 'juana@cempp.com';

    $parent = User::where('email', $parentEmail)->first();
    if (!$parent) die("Parent not found\n");

    $student = User::find($studentId);
    if (!$student) die("Student not found\n");

    echo "Parent ID: {$parent->id}, Student ID: {$student->id}\n";

    // Simulate Repository Logic
    // public function findPivot($familiaId, $estudianteId)

    $pivot = UsersFamilia::where('familia_id', $parent->id)
        ->where('estudiante_id', $student->id)
        ->first();

    if ($pivot) {
        echo "Pivot found. ID: {$pivot->id}. Deleted at: " . ($pivot->deleted_at ?? 'NULL') . "\n";
    } else {
        echo "Pivot NOT found.\n";
    }

    echo "Debug Script Completed Successfully.\n";
} catch (\Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
