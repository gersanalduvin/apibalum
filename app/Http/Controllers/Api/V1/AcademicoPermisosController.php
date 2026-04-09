<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AsignaturaGradoDocenteService;
use Illuminate\Http\Request;

class AcademicoPermisosController extends Controller
{
    public function __construct(private AsignaturaGradoDocenteService $service) {}

    public function index(Request $request)
    {
        $grupoId = $request->input('grupo_id');
        if (!$grupoId) {
            return response()->json(['message' => 'El grupo_id es requerido'], 400);
        }

        $asignaturas = $this->service->getByGrupo($grupoId);

        return response()->json(['data' => $asignaturas]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'asignaciones' => 'required|array',
            'asignaciones.*.id' => 'required|exists:not_asignatura_grado_docente,id',
            // No strict validation on dates as they can be null or valid dates
        ]);

        try {
            $this->service->updatePermisosMasivo($request->input('asignaciones'));
            return response()->json(['message' => 'Permisos actualizados correctamente']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar permisos: ' . $e->getMessage()], 500);
        }
    }
}
