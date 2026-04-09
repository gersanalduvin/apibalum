<?php

use App\Models\LessonPlan;
use Illuminate\Support\Facades\DB;

$date = '2026-01-07';
$periodo = 1; // Assuming 2026 is ID 1 based on previous context, or we can fetch.
// Actually from screenshot "Periodo Lectivo 2026".

$plans = LessonPlan::with(['groups', 'user', 'asignatura'])
    ->whereDate('start_date', $date)
    ->where('is_submitted', true)
    ->get();

echo "Plans found in List (count: " . $plans->count() . "):\n";
echo str_pad("Plan ID", 10) . str_pad("User ID", 10) . str_pad("Asignatura", 15) . str_pad("Groups", 15) . str_pad("Official T. ID", 15) . "Status\n";
echo str_repeat("-", 80) . "\n";

$matchCount = 0;

foreach ($plans as $plan) {
    if ($plan->groups->isEmpty()) {
        echo str_pad($plan->id, 10) . str_pad($plan->user_id, 10) . "NO GROUPS\n";
        continue;
    }

    $groupId = $plan->groups->first()->grupo_id;

    // Find official teacher
    $officialAssignment = DB::table('not_asignatura_grado_docente')
        ->where('asignatura_grado_id', $plan->asignatura_id)
        ->where('grupo_id', $groupId)
        ->whereNull('deleted_at')
        ->first();

    $officialId = $officialAssignment ? $officialAssignment->user_id : 'NONE';

    $status = 'OK';
    if ($officialId === 'NONE') {
        $status = 'NO ASSIGNMENT';
    } elseif ($officialId != $plan->user_id) {
        $status = 'MISMATCH';
    } else {
        $matchCount++;
    }

    echo str_pad($plan->id, 10) .
        str_pad($plan->user_id, 10) .
        str_pad($plan->asignatura_id, 15) .
        str_pad($groupId, 15) .
        str_pad($officialId, 15) .
        $status . "\n";
}

echo "\nTotal Matches for Stats (Expected 'Planificaron'): " . $matchCount . "\n";
