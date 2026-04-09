<?php

use App\Models\User;
use App\Models\UsersFamilia;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = 'juana@cempp.com';
$user = User::where('email', $email)->first();

echo "User: " . ($user ? $user->email . " (ID: {$user->id}, Tipo: {$user->tipo_usuario})" : "Not Found") . "\n";

if ($user) {
    if ($user->tipo_usuario !== 'familia') {
        echo "WARNING: User type is not 'familia'. It is '{$user->tipo_usuario}'.\n";
    }

    echo "Checking relations in users_familia...\n";
    $relations = DB::table('users_familia')->where('familia_id', $user->id)->get();

    if ($relations->isEmpty()) {
        echo "No records found in users_familia for familia_id = {$user->id}\n";
    } else {
        foreach ($relations as $rel) {
            echo "- Linked to Student ID: {$rel->estudiante_id} (Deleted At: " . ($rel->deleted_at ?? 'Active') . ")\n";
            $student = User::find($rel->estudiante_id);
            if ($student) {
                echo "  -> Student Name: {$student->primer_nombre} {$student->primer_apellido}\n";
                echo "  -> Student Type: {$student->tipo_usuario}\n";
            } else {
                echo "  -> Student User NOT FOUND\n";
            }
        }
    }

    echo "\nTesting Repository Method:\n";
    $repo = app(\App\Repositories\UsersFamiliaRepository::class);
    $students = $repo->getStudentsByFamily($user->id);
    echo "Repository returned " . $students->count() . " students.\n";
    foreach ($students as $s) {
        echo "- {$s->primer_nombre} {$s->primer_apellido} (ID: {$s->id})\n";
    }
}
