<?php
$d = App\Models\ReciboDetalle::where('concepto', 'like', '%MENSUALIDAD DE ENERO 2026%')->first();
if (!$d) {
    echo "ReciboDetalle not found.\n";
    exit;
}
$recibo = $d->recibo;
$userId = $recibo->user_id;
echo "Recibo ID: {$recibo->id} | User ID: {$userId}\n";

$aranceles = App\Models\UsersAranceles::where('user_id', $userId)->get();
echo "Total Aranceles for User: " . $aranceles->count() . "\n";

foreach ($aranceles as $ua) {
    $cppd = $ua->rubro;
    $isLinked = ($d->rubro_id == $ua->id) ? "  <-- LINKED TO RECIBO" : "";

    echo "UA ID: {$ua->id} | CPPD ID: " . ($cppd->id ?? 'NULL') . " | CPPD Name: " . ($cppd->nombre ?? 'NULL') . " | Order: " . ($cppd->orden_mes ?? 'NULL') . " | Status: {$ua->estado} | Balance: {$ua->saldo_actual}{$isLinked}\n";
}
