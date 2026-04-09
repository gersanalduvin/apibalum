<?php

namespace App\Services;

use App\Repositories\Contracts\ReporteRetiradosRepositoryInterface;
use App\Models\ConfPeriodoLectivo;
use Barryvdh\Snappy\Facades\SnappyPdf as Pdf;
use App\Utils\SimpleXlsxGenerator;
use Carbon\Carbon;

class ReporteRetiradosService
{
    protected $repository;

    public function __construct(ReporteRetiradosRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getReportData(int $periodoLectivoId)
    {
        $periodo = ConfPeriodoLectivo::find($periodoLectivoId);
        $alumnos = $this->repository->getRetiradosByPeriod($periodoLectivoId);

        // Map and format if necessary
        $alumnos = $alumnos->map(function ($alumno) {
            $alumno->nombre_completo = trim("{$alumno->primer_nombre} {$alumno->segundo_nombre} {$alumno->primer_apellido} {$alumno->segundo_apellido}");
            $alumno->fecha_retiro_formatted = $alumno->fecha_retiro ? Carbon::parse($alumno->fecha_retiro)->format('d/m/Y') : 'N/A';
            // Keep original for reference if needed, but override for display
            $alumno->fecha_retiro = $alumno->fecha_retiro_formatted;
            return $alumno;
        });

        return [
            'periodo' => $periodo,
            'alumnos' => $alumnos
        ];
    }

    public function generatePdf(int $periodoLectivoId)
    {
        $data = $this->getReportData($periodoLectivoId);
        $periodo = $data['periodo'];
        $alumnos = $data['alumnos'];

        $titulo = 'REPORTE DE ALUMNOS RETIRADOS';
        $subtitulo1 = 'Período: ' . ($periodo->nombre ?? $periodoLectivoId);
        $subtitulo2 = 'Generado el: ' . now()->format('d/m/Y H:i');
        $nombreInstitucion = config('app.nombre_institucion', 'Institución Educativa');

        $headerHtml = view()->make('pdf.header', compact('titulo', 'subtitulo1', 'subtitulo2', 'nombreInstitucion'))->render();

        $pdf = Pdf::loadView('reportes.alumnos.retirados', compact('alumnos', 'periodo'))
            ->setPaper('letter')
            ->setOrientation('portrait')
            ->setOption('margin-top', 35)
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 20)
            ->setOption('margin-left', 10)
            ->setOption('header-html', $headerHtml)
            ->setOption('header-spacing', 5)
            ->setOption('footer-left', 'Fecha y hora: [date] [time]')
            ->setOption('footer-right', 'Página [page] de [toPage]')
            ->setOption('footer-spacing', 5);

        return $pdf->stream('alumnos_retirados_' . now()->format('Ymd_His') . '.pdf');
    }

    public function generateExcel(int $periodoLectivoId)
    {
        $data = $this->getReportData($periodoLectivoId);
        $alumnos = $data['alumnos'];
        $periodo = $data['periodo'];

        $rows = [];
        foreach ($alumnos as $index => $alumno) {
            $rows[] = [
                $index + 1,
                $alumno->nombre_completo,
                $alumno->grado_nombre . ' - ' . $alumno->seccion_nombre,
                $alumno->turno_nombre,
                ucfirst(str_replace('_', ' ', $alumno->estado)),
                $alumno->fecha_retiro,
                $alumno->observaciones ?? ''
            ];
        }

        $headings = [
            '#',
            'Nombre Completo',
            'Grado/Sección',
            'Turno',
            'Estado',
            'Fecha Retiro',
            'Observaciones'
        ];

        $binary = SimpleXlsxGenerator::generate($headings, $rows);

        return response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="alumnos_retirados_' . now()->format('Ymd_His') . '.xlsx"'
        ]);
    }
}
