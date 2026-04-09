<?php
$d = App\Models\ReciboDetalle::where('concepto', 'like', '%MENSUALIDAD DE ENERO 2026%')->first();
if (!$d) {
    echo "ReciboDetalle not found.\n";
    exit;
}

$recibo = $d->recibo;
echo "Recibo ID: " . $recibo->id . "\n";
echo "Recibo Created At: " . $recibo->created_at . "\n";

$ua = $d->rubro;
if ($ua) {
    $cppd = $ua->rubro;
    if ($cppd) {
        echo "ConfigPlanPagoDetalle ID: " . $cppd->id . "\n";
        echo "Nombre: " . $cppd->nombre . "\n";
        echo "CPPD Created At: " . $cppd->created_at . "\n";
        echo "CPPD Updated At: " . $cppd->updated_at . "\n";

        if ($cppd->updated_at > $recibo->created_at) {
            echo "WARNING: Config was updated AFTER receipt creation!\n";
        } else {
            echo "Config was updated BEFORE receipt creation.\n";
        }

        echo "\nSiblings in the same Plan:\n";
        $siblings = App\Models\ConfigPlanPagoDetalle::where('plan_pago_id', $cppd->plan_pago_id)->get();
        foreach ($siblings as $sib) {
            echo "ID: {$sib->id} | Nombre: {$sib->nombre} | Orden: {$sib->orden_mes}\n";
        }
    } else {
        echo "CPPD not found.\n";
    }
} else {
    echo "UsersAranceles not found.\n";
}
