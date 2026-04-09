<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\BuscarReciboService;
use App\Services\ReciboService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class BuscarReciboController extends Controller
{
    public function __construct(
        private BuscarReciboService $buscarReciboService,
        private ReciboService $reciboService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'numero_recibo' => 'nullable|string|max:100',
                'nombre_usuario' => 'nullable|string|max:255',
                'fecha_inicio' => 'nullable|date',
                'fecha_fin' => 'nullable|date',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $filters = $request->only(['numero_recibo', 'nombre_usuario', 'fecha_inicio', 'fecha_fin', 'estado_not']);
            $perPage = (int) $request->input('per_page', 15);
            $recibos = $this->buscarReciboService->listar($filters, $perPage);
            return $this->successResponse($recibos, 'Recibos buscados exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse('Error al buscar recibos: ' . $e->getMessage(), [], 400);
        }
    }

    public function anular(string $id): JsonResponse
    {
        try {
            $recibo = $this->reciboService->anularRecibo((int)$id);
            return $this->successResponse($recibo, 'Recibo anulado exitosamente');
        } catch (Exception $e) {
            $statusCode = str_contains($e->getMessage(), 'no encontrado') ? 404 : 400;
            return $this->errorResponse('Error al anular el recibo: ' . $e->getMessage(), [], $statusCode);
        }
    }

    public function imprimir(string $id)
    {
        try {
            return $this->reciboService->generarPdf((int)$id);
        } catch (Exception $e) {
            return $this->errorResponse('Error al generar el PDF: ' . $e->getMessage(), [], 500);
        }
    }
}
