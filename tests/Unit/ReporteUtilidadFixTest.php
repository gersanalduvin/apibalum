<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Producto;
use App\Models\InventarioMovimiento;
use App\Models\InventarioKardex;
use App\Services\ReporteUtilidadInventarioService;
use App\Services\MovimientoInventarioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ReporteUtilidadFixTest extends TestCase
{
    // use RefreshDatabase; // We might not want to wipe the whole DB if checking against existing data, but for a clean test it's better.
    // Given the user's environment, I'll rely on creating new data and cleaning it up or just ignoring existing data by filtering.

    public function test_report_uses_historical_price()
    {
        // 1. Create a product with initial price
        $producto = Producto::create([
            'codigo' => 'TEST-HIST-PRICE',
            'nombre' => 'Producto Test Histórico',
            'precio_venta' => 100, // Precio Inicial
            'costo_promedio' => 50,
            'stock_actual' => 0,
            'moneda' => false,
            'activo' => true
        ]);

        $movService = app(MovimientoInventarioService::class);
        $reportService = app(ReporteUtilidadInventarioService::class);

        // 2. Movement 1 (January): Price 100
        $fecha1 = Carbon::create(2026, 1, 15);
        $movService->createMovimiento([
            'producto_id' => $producto->id,
            'tipo_movimiento' => 'entrada',
            'cantidad' => 10,
            'costo_unitario' => 50,
            'documento_fecha' => $fecha1,
            'observaciones' => 'Entrada Inicial'
        ]);

        // Force update price in movement/kardex if not captured (though my previous fix should capture it)
        // But wait, createMovimiento captures current product price. So it captured 100.

        // 3. Change Product Price to 200
        $producto->update(['precio_venta' => 200]);

        // 4. Movement 2 (February): Price 200 should be captured
        $fecha2 = Carbon::create(2026, 2, 15);
        $movService->createMovimiento([
            'producto_id' => $producto->id,
            'tipo_movimiento' => 'entrada',
            'cantidad' => 10,
            'costo_unitario' => 50,
            'documento_fecha' => $fecha2,
            'observaciones' => 'Segunda Entrada'
        ]);

        // 5. Run Report for January (should see Price 100)
        $reportJan = $reportService->getReportePorMes(2026, 1, ['buscar' => 'TEST-HIST-PRICE']);
        $itemJan = $reportJan['productos'][0];

        // 6. Run Report for February (should see Price 200, if we assume weighted or latest... actually latest Kardex has price at that moment)
        // The last kardex in Feb has price 200.
        $reportFeb = $reportService->getReportePorMes(2026, 2, ['buscar' => 'TEST-HIST-PRICE']);
        $itemFeb = $reportFeb['productos'][0];

        echo "\n--- Result Analysis ---\n";
        echo "Jan Price (Expected 100): " . $itemJan['precio_venta'] . "\n";
        echo "Feb Price (Expected 200): " . $itemFeb['precio_venta'] . "\n";

        // Assertions
        $this->assertEquals(100, $itemJan['precio_venta'], "January report should show historical price 100");
        $this->assertEquals(200, $itemFeb['precio_venta'], "February report should show updated price 200");
        $this->assertEquals(1000, $itemJan['total_venta_potencial'], "Jan Total Venta should be 10 * 100 = 1000");
        $this->assertEquals(4000, $itemFeb['total_venta_potencial'], "Feb Total Venta should be 20 * 200 = 4000"); // 10 old + 10 new = 20 stock. Last price is 200.
        // Note: The report logic uses the Price of the LAST Kardex in the period.
        // In Jan, last kardex had price 100.
        // In Feb, last kardex had price 200.
        // Valuation is usually Stock * CurrentPrice (at that time).

        // Clean up
        InventarioMovimiento::where('producto_id', $producto->id)->delete();
        InventarioKardex::where('producto_id', $producto->id)->delete();
        $producto->forceDelete();
    }
}
