<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\NotAsignaturaGradoDocente;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Services\CalificacionService;

echo "--- Testing Assignment Ownership Validation ---\n";

try {
    // 1. Find a teacher
    $teacher = User::where('tipo_usuario', 'docente')->where('superadmin', false)->first();
    if (!$teacher) {
        throw new \Exception("No non-superadmin teacher found for testing.");
    }
    echo "Testing as Teacher: " . $teacher->nombre_completo . " (ID: " . $teacher->id . ")\n";

    // 2. Find an assignment NOT belonging to this teacher
    $otherAssignment = NotAsignaturaGradoDocente::where('user_id', '!=', $teacher->id)->first();
    if (!$otherAssignment) {
        throw new \Exception("No assignment found belonging to another teacher.");
    }
    echo "Attempting to access Assignment ID: " . $otherAssignment->id . " (Owned by User ID: " . $otherAssignment->user_id . ")\n";

    // 3. Login as the teacher
    Auth::login($teacher);

    // 4. Try to access metadata via service
    $service = app(CalificacionService::class);

    echo "\nTesting getAssignmentMetadata:\n";
    try {
        $service->getAssignmentMetadata($otherAssignment->id);
        echo "FAILURE: Access should have been denied but was granted.\n";
    } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
        echo "SUCCESS: Access denied as expected. Message: " . $e->getMessage() . "\n";
    }

    echo "\nTesting getGradesByAssignmentId:\n";
    try {
        $service->getGradesByAssignmentId($otherAssignment->id, 1); // corte 1
        echo "FAILURE: Access should have been denied but was granted.\n";
    } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
        echo "SUCCESS: Access denied as expected. Message: " . $e->getMessage() . "\n";
    }

    // 5. Test as Superadmin
    $superadmin = User::where('superadmin', true)->first();
    if ($superadmin) {
        echo "\nTesting as Superadmin: " . $superadmin->nombre_completo . "\n";
        Auth::login($superadmin);
        try {
            $service->getAssignmentMetadata($otherAssignment->id);
            echo "SUCCESS: Superadmin granted access as expected.\n";
        } catch (\Exception $e) {
            echo "FAILURE: Superadmin denied access. Error: " . $e->getMessage() . "\n";
        }
    }
} catch (\Exception $e) {
    echo "ERROR during test: " . $e->getMessage() . "\n";
}
