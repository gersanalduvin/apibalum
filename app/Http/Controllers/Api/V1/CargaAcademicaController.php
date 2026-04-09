<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AsignaturaGradoDocenteService;
use Illuminate\Http\Request;
use Barryvdh\Snappy\Facades\SnappyPdf;

class CargaAcademicaController extends Controller
{
    public function __construct(private AsignaturaGradoDocenteService $service) {}

    public function index(Request $request)
    {
        $filters = $request->only(['periodo_lectivo_id', 'materia_id', 'grado_id', 'grupo_id']);
        $data = $this->service->getCargaAcademica($filters);

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Carga académica obtenida correctamente'
        ]);
    }

    public function filtros(Request $request)
    {
        $periodoLectivoId = $request->get('periodo_lectivo_id');
        if (!$periodoLectivoId) {
            return response()->json(['success' => false, 'message' => 'Periodo lectivo no proporcionado'], 400);
        }

        $filtros = $this->service->getFiltros($periodoLectivoId);

        return response()->json([
            'success' => true,
            'data' => $filtros
        ]);
    }

    public function exportPdf(Request $request)
    {
        $filters = $request->only(['periodo_lectivo_id', 'materia_id', 'grado_id', 'grupo_id']);
        $data = $this->service->getCargaAcademica($filters);

        $titulo = 'Reporte de Carga Académica';
        $nombreInstitucion = config('app.nombre_institucion', 'MINISTERIO DE EDUCACIÓN');
        $subtitulo1 = 'Fecha de generación: ' . date('d/m/Y H:i');

        $headerHtml = view()->make('pdf.header', compact('titulo', 'nombreInstitucion', 'subtitulo1'))->render();

        $html = view('reportes.carga_academica.pdf', [
            'data' => $data,
            'titulo' => $titulo
        ])->render();

        $pdf = SnappyPdf::loadHTML($html)
            ->setPaper('letter')
            ->setOrientation('portrait')
            ->setOption('margin-top', 35)
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 20)
            ->setOption('margin-left', 10)
            ->setOption('encoding', 'utf-8')
            ->setOption('enable-local-file-access', true)
            ->setOption('header-html', $headerHtml)
            ->setOption('header-spacing', 5)
            ->setOption('footer-left', 'Fecha y hora: [date] [time]')
            ->setOption('footer-right', 'Página [page] de [toPage]')
            ->setOption('footer-font-size', 8)
            ->setOption('footer-spacing', 5)
            ->setOption('load-error-handling', 'ignore');

        return $pdf->stream('reporte_carga_academica.pdf');
    }
}
