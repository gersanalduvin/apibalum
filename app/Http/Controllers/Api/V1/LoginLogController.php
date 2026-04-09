<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\LoginLogService;
use Illuminate\Http\Request;

class LoginLogController extends Controller
{
    protected $loginLogService;

    /**
     * LoginLogController constructor.
     *
     * @param LoginLogService $loginLogService
     */
    public function __construct(LoginLogService $loginLogService)
    {
        $this->loginLogService = $loginLogService;
    }

    /**
     * Display a listing of the login logs.
     */
    public function index(Request $request)
    {
        try {
            // Extract filters from request
            $filters = $request->only(['fecha_inicio', 'fecha_fin', 'tipo_usuario', 'search', 'unique']);
            $perPage = $request->input('per_page', 15);

            $logs = $this->loginLogService->getPaginatedLogs($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $logs
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los registros de inicio de sesión',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
