<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ReporteCierreCajaRequest;
use App\Services\ReporteCierreCajaService;
use Illuminate\Http\JsonResponse;

class ReporteCierreCajaController extends Controller
{
    public function __construct(private ReporteCierreCajaService $service) {}

    public function detalles(ReporteCierreCajaRequest $request): JsonResponse
    {
        $tipo = $request->input('tipo');
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $data = $this->service->obtenerDetalles($tipo, $fechaInicio, $fechaFin);
        return $this->successResponse($data, 'Detalles de recibos obtenidos exitosamente');
    }

    public function conceptos(ReporteCierreCajaRequest $request): JsonResponse
    {
        $tipo = $request->input('tipo');
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $data = $this->service->obtenerDetallesPorConcepto($tipo, $fechaInicio, $fechaFin);
        return $this->successResponse($data, 'Detalles de recibos agrupados por concepto obtenidos exitosamente');
    }

    public function paquetes(ReporteCierreCajaRequest $request): JsonResponse
    {
        $tipo = $request->input('tipo');
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $data = $this->service->obtenerDetallesPorConcepto($tipo, $fechaInicio, $fechaFin, true);
        return $this->successResponse($data, 'Detalles de paquetes (aranceles) obtenidos exitosamente');
    }

    public function exportDetallesPdf(ReporteCierreCajaRequest $request)
    {
        $meta = [
            'tipo' => $request->input('tipo'),
            'fecha_inicio' => $request->input('fecha_inicio'),
            'fecha_fin' => $request->input('fecha_fin'),
        ];
        $detalles = $this->service->obtenerDetalles($meta['tipo'], $meta['fecha_inicio'], $meta['fecha_fin']);
        return $this->service->generarPdfDetalles($meta, $detalles);
    }

    public function exportConceptosPdf(ReporteCierreCajaRequest $request)
    {
        $meta = [
            'tipo' => $request->input('tipo'),
            'fecha_inicio' => $request->input('fecha_inicio'),
            'fecha_fin' => $request->input('fecha_fin'),
        ];
        $conceptos = $this->service->obtenerDetallesPorConcepto($meta['tipo'], $meta['fecha_inicio'], $meta['fecha_fin']);
        return $this->service->generarPdfConceptos($meta, $conceptos);
    }

    public function exportPaquetesPdf(ReporteCierreCajaRequest $request)
    {
        $meta = [
            'tipo' => $request->input('tipo'),
            'fecha_inicio' => $request->input('fecha_inicio'),
            'fecha_fin' => $request->input('fecha_fin'),
        ];
        $conceptos = $this->service->obtenerDetallesPorConcepto($meta['tipo'], $meta['fecha_inicio'], $meta['fecha_fin'], true);
        return $this->service->generarPdfConceptos($meta, $conceptos);
    }

    public function exportDetallesExcel(ReporteCierreCajaRequest $request)
    {
        $meta = [
            'tipo' => $request->input('tipo'),
            'fecha_inicio' => $request->input('fecha_inicio'),
            'fecha_fin' => $request->input('fecha_fin'),
        ];
        $detalles = $this->service->obtenerDetalles($meta['tipo'], $meta['fecha_inicio'], $meta['fecha_fin']);
        return $this->service->generarExcelDetalles($meta, $detalles);
    }

    public function exportConceptosExcel(ReporteCierreCajaRequest $request)
    {
        $meta = [
            'tipo' => $request->input('tipo'),
            'fecha_inicio' => $request->input('fecha_inicio'),
            'fecha_fin' => $request->input('fecha_fin'),
        ];
        $conceptos = $this->service->obtenerDetallesPorConcepto($meta['tipo'], $meta['fecha_inicio'], $meta['fecha_fin']);
        return $this->service->generarExcelConceptos($meta, $conceptos);
    }

    public function exportPaquetesExcel(ReporteCierreCajaRequest $request)
    {
        $meta = [
            'tipo' => $request->input('tipo'),
            'fecha_inicio' => $request->input('fecha_inicio'),
            'fecha_fin' => $request->input('fecha_fin'),
        ];
        $conceptos = $this->service->obtenerDetallesPorConcepto($meta['tipo'], $meta['fecha_inicio'], $meta['fecha_fin'], true);
        return $this->service->generarExcelConceptos($meta, $conceptos);
    }
}
