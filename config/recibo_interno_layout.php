<?php

return [
    'page' => [
        'paper' => 'letter',
        'orientation' => 'portrait',
        'margin_top' => 5,
        'margin_right' => 5,
        'margin_bottom' => 5,
        'margin_left' => 5,
    ],
    'fields' => [
        // Coordenadas relativas en porcentaje (ajustables)
        'numero_recibo' => ['x' => 60, 'y' => 7],
        'fecha_dia' => ['x' => 6, 'y' => 13],
        'fecha_mes' => ['x' => 13, 'y' => 13],
        'fecha_anio' => ['x' => 19, 'y' => 13],
        'recibi_de' => ['x' => 14, 'y' => 17],
        'por_cordoba' => ['x' => 54, 'y' => 14],
        'por_dolar' => ['x' => 82, 'y' => 36],
        'cantidad_letras' => ['x' => 23, 'y' => 20, 'w' => 115], // w en mm
        'concepto' => ['x' => 19, 'y' => 26, 'w' => 115], // w en mm
        'efectivo_checkbox' => ['x' => 11, 'y' => 34],
        'cheque_checkbox' => ['x' => 25, 'y' => 70],
        'cheque_numero' => ['x' => 33, 'y' => 69],
        'banco' => ['x' => 62, 'y' => 69],
        'firma' => ['x' => 56, 'y' => 85],
    ],
    'font' => [
        'size' => 12,
        'family' => "'Courier New', Courier, monospace"
    ]
];
