<?php

namespace App\Services;

use App\Repositories\ConfigNotSemestreRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Barryvdh\Snappy\Facades\SnappyPdf;
use App\Utils\SimpleXlsxGenerator;

class ConfigNotSemestreService
{
    public function __construct(private ConfigNotSemestreRepository $repository) {}

    public function getSemestresPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getPaginatedWithParciales($filters, $perPage);
    }

    public function upsertSemestreAndParciales(array $data)
    {
        return DB::transaction(function () use ($data) {
            $parciales = $data['parciales'] ?? [];
            unset($data['parciales']);

            if (!empty($data['id'])) {
                $semestre = $this->repository->update((int) $data['id'], $data);
            } else {
                $semestre = $this->repository->create($data);
            }

            $this->repository->upsertParciales($semestre->id, $parciales);

            return $this->repository->find($semestre->id);
        });
    }

    public function deleteSemestre(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            return $this->repository->delete($id);
        });
    }

    public function deleteParcial(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            return $this->repository->deleteParcial($id);
        });
    }

    public function exportPdf(array $filters = [])
    {
        $rows = $this->repository->getPaginatedWithParciales($filters, 1000);
        $data = $rows->items();

        $html = view('pdf.config-not-semestre', [
            'semestres' => $data,
            'filters' => $filters,
        ])->render();

        $titulo = 'CONFIGURACIÓN - CORTES (SEMESTRES Y PARCIALES)';
        $subtitulo1 = 'Generado: ' . now()->format('d/m/Y H:i');
        $subtitulo2 = !empty($filters['semestre']) ? ('Filtro: ' . $filters['semestre']) : '';
        $nombreInstitucion = config('app.nombre_institucion', 'INSTITUCIÓN');
        $headerHtml = view()->make('pdf.header', compact('titulo', 'subtitulo1', 'subtitulo2', 'nombreInstitucion'))->render();

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
            ->setOption('footer-spacing', 5)
            ->setOption('load-error-handling', 'ignore');

        $nombreArchivo = 'config_cortes_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        return $pdf->download($nombreArchivo);
    }

    public function exportExcel(array $filters = [])
    {
        $rows = $this->repository->getPaginatedWithParciales($filters, 1000);
        $data = $rows->items();

        $headings = ['Semestre', 'Abreviatura', 'Orden', 'Periodo Lectivo', 'Parcial', 'Abrev', 'Inicio Corte', 'Fin Corte', 'Inicio Publicación', 'Fin Publicación', 'Orden Parcial'];
        $excelRows = [];
        foreach ($data as $semestre) {
            $nombreSemestre = $semestre->nombre ?? '';
            $abreviaturaSem = $semestre->abreviatura ?? '';
            $ordenSem = (string) ($semestre->orden ?? 0);
            $periodo = optional($semestre->periodoLectivo)->nombre ?? '';
            foreach ($semestre->parciales as $p) {
                $excelRows[] = [
                    $nombreSemestre,
                    $abreviaturaSem,
                    $ordenSem,
                    $periodo,
                    $p->nombre ?? '',
                    $p->abreviatura ?? '',
                    optional($p->fecha_inicio_corte)?->format('Y-m-d') ?? '',
                    optional($p->fecha_fin_corte)?->format('Y-m-d') ?? '',
                    optional($p->fecha_inicio_publicacion_notas)?->format('Y-m-d') ?? '',
                    optional($p->fecha_fin_publicacion_notas)?->format('Y-m-d') ?? '',
                    (string) ($p->orden ?? 0),
                ];
            }
        }

        $metaRows = [
            ['Reporte', 'Configuración - Cortes (Semestres y Parciales)'],
            ['Generado', now()->format('Y-m-d H:i')],
            !empty($filters['semestre']) ? ['Filtro', $filters['semestre']] : [],
            [],
        ];

        $metaRows = array_values(array_filter($metaRows, fn($r) => !empty($r)));

        $binary = SimpleXlsxGenerator::generateWithMeta($metaRows, $headings, $excelRows);
        return response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="configuracion_cortes.xlsx"'
        ]);
    }
}

