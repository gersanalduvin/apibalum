<?php

use App\Models\ReciboDetalle;
use App\Models\UsersAranceles;
use App\Models\ConfigPlanPagoDetalle;

$detalles = ReciboDetalle::with('recibo')->whereNotNull('rubro_id')->get();
$countTotal = 0;
$countInconsistent = 0;
$countValid = 0;
$countNotFound = 0;

foreach ($detalles as $detalle) {
    $countTotal++;

    // Check if rubro_id is a valid UsersAranceles for this user
    $ua = UsersAranceles::find($detalle->rubro_id);

    if ($ua) {
        if ($ua->user_id == $detalle->recibo->user_id) {
            $countValid++;
        } else {
            $countInconsistent++;
            // echo "Inconsistent: Detalle ID {$detalle->id}, rubro_id {$detalle->rubro_id} belongs to user {$ua->user_id}, but recibo belongs to user {$detalle->recibo->user_id}\n";
        }
    } else {
        // ID not found in UsersAranceles, check if it exists in ConfigPlanPagoDetalle
        $conf = ConfigPlanPagoDetalle::find($detalle->rubro_id);
        if ($conf) {
            $countInconsistent++;
            // echo "Inconsistent (Config ID): Detalle ID {$detalle->id}, rubro_id {$detalle->rubro_id} is a ConfigPlanPagoDetalle ID\n";
        } else {
            $countNotFound++;
            // echo "Not Found: Detalle ID {$detalle->id}, rubro_id {$detalle->rubro_id} not in UA nor Conf\n";
        }
    }
}

echo "\n--- Summary ---\n";
echo "Total checked: $countTotal\n";
echo "Valid assignments: $countValid\n";
echo "Inconsistent assignments (to fix): $countInconsistent\n";
echo "Unknown IDs: $countNotFound\n";
