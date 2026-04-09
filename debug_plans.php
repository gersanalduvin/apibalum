<?php
require 'c:/laragon6/www/apicmpp/vendor/autoload.php';
$app = require_once 'c:/laragon6/www/apicmpp/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$plans = App\Models\LessonPlan::orderBy('id', 'desc')->take(5)->get();
foreach ($plans as $p) {
    echo 'ID: ' . $p->id . ' | User: ' . $p->user_id . ' | Asig: ' . ($p->asignatura_id ?? 'NULL') . ' | General: ' . $p->is_general . ' | Created: ' . $p->created_at . PHP_EOL;
}
