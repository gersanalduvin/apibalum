<?php
$ua = App\Models\UsersAranceles::find(2);
if ($ua) {
    echo "UA ID: " . $ua->id . "\n";
    echo "User ID: " . $ua->user_id . "\n";
    echo "CPPD ID: " . $ua->rubro_id . "\n";
    echo "CPPD Name: " . ($ua->rubro->nombre ?? 'NULL') . "\n";
    echo "Importe Total: " . $ua->importe_total . "\n";
} else {
    echo "UsersAranceles ID 2 NOT FOUND.\n";
}
