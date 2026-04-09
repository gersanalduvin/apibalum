<?php

use App\Models\LessonPlan;
use Illuminate\Support\Facades\DB;

$plans = LessonPlan::with('groups')->get();
$updatedCount = 0;

echo "Checking " . $plans->count() . " plans...\n";

foreach ($plans as $plan) {
    // Check first group
    $firstGroup = $plan->groups->first();
    if (!$firstGroup) {
        // If lesson plan has no groups, we skip or handle differently.
        // Assuming plans validly have groups based on context
        continue;
    }

    $groupId = $firstGroup->grupo_id;

    // Find the OFFICIAL teacher for this subject + group
    $assignedTeacherId = DB::table('not_asignatura_grado_docente')
        ->where('asignatura_grado_id', $plan->asignatura_id)
        ->where('grupo_id', $groupId)
        ->whereNull('deleted_at')
        ->value('user_id');

    if ($assignedTeacherId) {
        if ($plan->user_id != $assignedTeacherId) {
            $oldUser = $plan->user_id;

            // Update the plan owner
            $plan->user_id = $assignedTeacherId;
            $plan->save();

            echo "Updated Plan ID {$plan->id} (Asig: {$plan->asignatura_id}, Grp: {$groupId}): Changed owner from User {$oldUser} to User {$assignedTeacherId}\n";
            $updatedCount++;
        }
    } else {
        echo "Warning: No teacher assigned for Plan ID {$plan->id} (Asig: {$plan->asignatura_id}, Grp: {$groupId})\n";
    }
}

echo "Total plans updated: $updatedCount\n";
