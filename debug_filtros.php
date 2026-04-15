<?php

use Illuminate\Support\Facades\DB;

// Test: conf_periodo_lectivos
try {
    $periodos = DB::table('conf_periodo_lectivos')
        ->whereNull('deleted_at')
        ->orderByDesc('created_at')
        ->select('id', 'nombre')
        ->get();
    echo "PERIODOS COUNT: " . $periodos->count() . "\n";
    echo json_encode($periodos->take(3), JSON_PRETTY_PRINT) . "\n";
} catch (\Exception $e) {
    echo "ERROR PERIODOS: " . $e->getMessage() . "\n";
}

// Test: config_grupos con periodo_lectivo_id
try {
    $grupos = DB::table('config_grupos as g')
        ->join('config_grado as cg', 'g.grado_id', '=', 'cg.id')
        ->join('config_seccion as cs', 'g.seccion_id', '=', 'cs.id')
        ->join('config_turnos as ct', 'g.turno_id', '=', 'ct.id')
        ->whereNull('g.deleted_at')
        ->select('g.id', DB::raw("CONCAT(cg.nombre, ' - ', cs.nombre, ' - ', ct.nombre) as nombre"), 'g.periodo_lectivo_id')
        ->orderBy('cg.nombre')
        ->limit(3)
        ->get();
    echo "GRUPOS COUNT: " . $grupos->count() . "\n";
    echo json_encode($grupos, JSON_PRETTY_PRINT) . "\n";
} catch (\Exception $e) {
    echo "ERROR GRUPOS: " . $e->getMessage() . "\n";
}

// Test: respuesta completa del servicio
try {
    $service = app(\App\Services\AsignaturaGradoDocenteService::class);
    $filtros = $service->getAdminFiltros();
    echo "FILTROS RESULT:\n";
    echo "  periodos: " . count($filtros['periodos']) . "\n";
    echo "  grupos: " . count($filtros['grupos']) . "\n";
    echo "  docentes: " . count($filtros['docentes']) . "\n";
} catch (\Exception $e) {
    echo "ERROR SERVICE: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
