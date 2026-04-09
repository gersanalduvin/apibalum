<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Audit;
use App\Models\InventarioMovimiento;
use App\Models\Producto;
use Carbon\Carbon;

$productoId = 29;
$davidId = 325;

echo "--- DEBUG LOGIC FOR PRODUCT {$productoId} ---\n";

$producto = Producto::find($productoId);
if (!$producto) {
    die("Producto no encontrado");
}

// 1. Get Audits
$audits = Audit::where('model_type', 'App\Models\Producto')
    ->where('model_id', $productoId)
    ->whereRaw("JSON_EXTRACT(old_values, '$.stock_actual') IS NOT NULL")
    ->where('user_id', $davidId)
    ->orderBy('created_at', 'asc')
    ->get();

echo "Audits encontrados (David): " . $audits->count() . "\n";

$stockRecuperadoDeReset = null;
$fechaRecuperadaDeReset = null;

// Simulate Filter Logic
$audits = $audits->map(function ($audit) use ($davidId, &$stockRecuperadoDeReset, &$fechaRecuperadaDeReset) {
    $newStock = $audit->new_values['stock_actual'] ?? null;
    $oldStock = $audit->old_values['stock_actual'] ?? null;
    $fecha = $audit->created_at;

    echo "  -> Audit {$audit->id} ($fecha): Old={$oldStock} -> New={$newStock}\n";

    if ($audit->user_id == $davidId && !is_null($newStock) && (int)$newStock == 1) {
        echo "     [WARN] Es RESET A 1. Guardando Old={$oldStock}.\n";
        if ($oldStock > 1) {
            $stockRecuperadoDeReset = $oldStock;
            $fechaRecuperadaDeReset = $audit->created_at;
        }
        $audit->ignore_move = true;
        return $audit;
    }
    $audit->ignore_move = false;
    return $audit;
});

if ($stockRecuperadoDeReset) {
    echo "  [INFO] FORZANDO BASE desde Reset: {$stockRecuperadoDeReset} (Fecha: {$fechaRecuperadaDeReset})\n";
    $primerAudit = new Audit();
    $primerAudit->id = 999999;
    $primerAudit->created_at = $fechaRecuperadaDeReset;
    $primerAudit->old_values = ['stock_actual' => $stockRecuperadoDeReset];
    $audits->prepend($primerAudit);
}

if ($audits->isEmpty()) {
    echo "  [INFO] No audits de David. Buscando Fallback...\n";
    $audits = Audit::where('model_type', 'App\Models\Producto')
        ->where('model_id', $productoId)
        ->whereRaw("JSON_EXTRACT(old_values, '$.stock_actual') IS NOT NULL")
        ->whereRaw("CAST(JSON_EXTRACT(old_values, '$.stock_actual') AS DECIMAL(10,2)) > 0")
        ->orderBy('created_at', 'asc')
        ->limit(1)
        ->get();
    echo "  -> Audits Fallback: " . $audits->count() . "\n";
}

if ($audits->isEmpty()) {
    die("  [RESULT] SKIP. No audits validos.\n");
}

$primerAudit = $audits->first();
$stockBase = $primerAudit->old_values['stock_actual'] ?? 0;
$fechaBase = Carbon::parse($primerAudit->created_at)->subSeconds(5);

echo "  -> CANDIDATO BASE: Date={$fechaBase}, Stock={$stockBase}\n";

if ($stockBase > 0) {
    $entradaMasiva = InventarioMovimiento::where('producto_id', $productoId)
        ->where('observaciones', 'like', '%Entrada Masiva%')
        ->first();

    if ($entradaMasiva) {
        $fechaMasiva = Carbon::parse($entradaMasiva->documento_fecha);
        echo "  -> ENTRADA MASIVA encontrada: Date={$fechaMasiva}, Cant={$entradaMasiva->cantidad}\n";

        if ($fechaMasiva->lte($fechaBase)) {
            echo "  [RESULT] SKIP BASE. Masiva ({$fechaMasiva}) es PREVIA/IGUAL a Audit ({$fechaBase}).\n";
        } else {
            echo "  [RESULT] CREATE BASE & DELETE MASIVA. Audit ({$fechaBase}) es ANTERIOR a Masiva ({$fechaMasiva}).\n";
        }
    } else {
        echo "  [RESULT] CREATE BASE. Normal (No hay masiva).\n";
    }
} else {
    echo "  [RESULT] SKIP. Stock base is 0.\n";
}
