<?php

use App\Models\ConfigPlanPago;
use App\Models\ConfigPlanPagoDetalle;
use App\Models\ConfPeriodoLectivo;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Forzar un usuario para la auditoría (opcional si el boot lo requiere)
$admin = \App\Models\User::first();
if ($admin) {
    Auth::login($admin);
}

$periodo = ConfPeriodoLectivo::where('nombre', '2026')->first();
if (!$periodo) {
    echo "Error: Periodo 2026 no encontrado.\n";
    exit(1);
}

$meses = [
    'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
    'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'
];

$planesData = [
    [
        'nombre' => 'GENERAL 2026',
        'mensualidad' => 1400,
        'matricula' => 1100,
        'examenes' => 300,
        'plataforma' => 360,
    ],
    [
        'nombre' => 'PLAN INCLUSIVO 2026',
        'mensualidad' => 2220,
        'matricula' => 1100,
        'examenes' => 300,
        'plataforma' => 360,
    ]
];

try {
    DB::beginTransaction();

    foreach ($planesData as $data) {
        echo "Creando plan: {$data['nombre']}...\n";
        
        $plan = ConfigPlanPago::create([
            'nombre' => $data['nombre'],
            'estado' => 1,
            'periodo_lectivo_id' => $periodo->id,
            'is_synced' => 1,
            'version' => 1
        ]);

        // 1. Matrícula
        ConfigPlanPagoDetalle::create([
            'plan_pago_id' => $plan->id,
            'codigo' => 'MAT-' . $plan->id,
            'nombre' => 'MATRÍCULA 2026',
            'importe' => $data['matricula'],
            'es_colegiatura' => false,
            'moneda' => 0, // Córdobas
            'fecha_vencimiento' => Carbon::create(2026, 1, 31),
        ]);

        // 2. Mensualidades
        foreach ($meses as $index => $mes) {
            $mesNum = $index + 1;
            ConfigPlanPagoDetalle::create([
                'plan_pago_id' => $plan->id,
                'codigo' => 'MEN-' . strtoupper(substr($mes, 0, 3)) . '-' . $plan->id,
                'nombre' => 'MENSUALIDAD DE ' . strtoupper($mes) . ' 2026',
                'importe' => $data['mensualidad'],
                'es_colegiatura' => true,
                'asociar_mes' => $mes,
                'moneda' => 0, // Córdobas
                'fecha_vencimiento' => Carbon::create(2026, $mesNum, 1)->endOfMonth(),
            ]);
        }

        // 3. Exámenes
        ConfigPlanPagoDetalle::create([
            'plan_pago_id' => $plan->id,
            'codigo' => 'EXA-' . $plan->id,
            'nombre' => 'EXAMENES 2026',
            'importe' => $data['examenes'],
            'es_colegiatura' => false,
            'moneda' => 0,
            'fecha_vencimiento' => Carbon::create(2026, 11, 30),
        ]);

        // 4. Plataforma
        ConfigPlanPagoDetalle::create([
            'plan_pago_id' => $plan->id,
            'codigo' => 'PLAT-' . $plan->id,
            'nombre' => 'PLATAFORMA',
            'importe' => $data['plataforma'],
            'es_colegiatura' => false,
            'moneda' => 0,
            'fecha_vencimiento' => Carbon::create(2026, 2, 28),
        ]);
    }

    DB::commit();
    echo "Planes creados exitosamente.\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
