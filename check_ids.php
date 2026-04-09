<?php

use App\Models\ConfigTurnos;
use App\Models\ConfigGrado;

echo "Turnos:\n";
foreach (ConfigTurnos::all() as $t) {
    echo "ID: {$t->id} - {$t->nombre}\n";
}

echo "\nGrados:\n";
foreach (ConfigGrado::all() as $g) {
    echo "ID: {$g->id} - {$g->nombre}\n";
}
