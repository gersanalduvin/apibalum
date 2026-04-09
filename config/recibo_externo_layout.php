<?php

return [
    'page' => [
        'paper' => 'letter',
        'orientation' => 'portrait',
        'margin_top' => 5,   // mm
        'margin_right' => 5, // mm
        'margin_bottom' => 5, // mm
        'margin_left' => 5,  // mm
    ],
    /*'fields' => [
        // Coordenadas relativas (porcentaje) respecto al área imprimible
        // Ajustables para calibración en impresora real
        'numero_recibo' => ['x' => 60, 'y' => 16],
        'fecha_dia' => ['x' => 11, 'y' => 22],
        'fecha_mes' => ['x' => 17, 'y' => 22],
        'fecha_anio' => ['x' => 23, 'y' => 22],
        'recibi_de' => ['x' => 13, 'y' => 36],
        'por_cordoba' => ['x' => 67, 'y' => 36],
        'por_dolar' => ['x' => 82, 'y' => 36],
        'cantidad_letras' => ['x' => 22, 'y' => 42],
        'concepto' => ['x' => 22, 'y' => 48],
        'efectivo_checkbox' => ['x' => 13, 'y' => 69],
        'cheque_checkbox' => ['x' => 24, 'y' => 69],
        'cheque_numero' => ['x' => 33, 'y' => 69],
        'banco' => ['x' => 62, 'y' => 69],
        'firma' => ['x' => 56, 'y' => 85],
    ],*/
    'fields' => [
        // Coordenadas generadas por el Calibrador
        'numero_recibo' => ['x' => 75.5, 'y' => 11.96],
        'fecha_dia' => ['x' => 5, 'y' => 15.81],
        'fecha_mes' => ['x' => 10, 'y' => 15.81],
        'fecha_anio' => ['x' => 19, 'y' => 15.81],
        'recibi_de' => ['x' => 16, 'y' => 22],
        'por_cordoba' => ['x' => 91, 'y' => 19],
        'por_dolar' => ['x' => 82, 'y' => 12],
        'cantidad_letras' => ['x' => 26, 'y' => 26.5, 'w' => 130], // w en mm
        'concepto' => ['x' => 22, 'y' => 30.5, 'w' => 140], // w en mm
        'efectivo_checkbox' => ['x' => 12, 'y' => 48.5],
        'cheque_checkbox' => ['x' => 22, 'y' => 33],
        'cheque_numero' => ['x' => 31, 'y' => 33],
        'banco' => ['x' => 49, 'y' => 34],
        'firma' => ['x' => 27, 'y' => 39],
    ],

    'font' => [
        'size' => 12, // Ajustado para Courier que es mas ancha
        'family' => "'Courier New', Courier, monospace"
    ]
];
