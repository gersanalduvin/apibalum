<?php

namespace App\Services;

use App\Repositories\ConfigNotEscalaRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Barryvdh\Snappy\Facades\SnappyPdf;
use App\Utils\SimpleXlsxGenerator;

class ConfigNotEscalaService
{
    public function __construct(private ConfigNotEscalaRepository $repository) {}

    public function getEscalasPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getPaginatedWithDetails($filters, $perPage);
    }

    public function upsertEscalaAndDetalles(array $data)
    {
        return DB::transaction(function () use ($data) {
            $detalles = $data['detalles'] ?? [];
            unset($data['detalles']);

            if (!empty($data['id'])) {
                $escala = $this->repository->update((int) $data['id'], $data);
            } else {
                $escala = $this->repository->create($data);
            }

            $this->repository->upsertDetalles($escala->id, $detalles);

            return $this->repository->find($escala->id);
        });
    }

    public function deleteEscala(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            return $this->repository->delete($id);
        });
    }

    public function deleteDetalle(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            return $this->repository->deleteDetalle($id);
        });
    }

    public function exportPdf(array $filters = [])
    {
        $rows = $this->repository->getPaginatedWithDetails($filters, 1000);
        $data = $rows->items();

        $html = view('pdf.config-not-escala', [
            'escalas' => $data,
            'filters' => $filters,
        ])->render();

        $titulo = 'CONFIGURACIÓN - ESCALA DE NOTAS';
        $subtitulo1 = 'Generado: ' . now()->format('d/m/Y H:i');
        $subtitulo2 = !empty($filters['notas']) ? ('Filtro: ' . $filters['notas']) : '';
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

        $nombreArchivo = 'config_escala_notas_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        return $pdf->download($nombreArchivo);
    }

    public function exportExcel(array $filters = [])
    {
        $rows = $this->repository->getPaginatedWithDetails($filters, 1000);
        $data = $rows->items();

        $headings = ['Escala', 'Nota', 'Abreviatura', 'Rango Inicio', 'Rango Fin', 'Orden'];
        $excelRows = [];
        foreach ($data as $escala) {
            $nombreEscala = $escala->nombre ?? '';
            foreach ($escala->detalles as $d) {
                $excelRows[] = [
                    $nombreEscala,
                    $d->nombre ?? '',
                    $d->abreviatura ?? '',
                    (string) ($d->rango_inicio ?? 0),
                    (string) ($d->rango_fin ?? 0),
                    (string) ($d->orden ?? 0),
                ];
            }
        }

        $metaRows = [
            ['Reporte', 'Configuración - Escala de Notas'],
            ['Generado', now()->format('Y-m-d H:i')],
            !empty($filters['notas']) ? ['Filtro', $filters['notas']] : [],
            [],
        ];

        $metaRows = array_values(array_filter($metaRows, fn($r) => !empty($r)));

        $binary = SimpleXlsxGenerator::generateWithMeta($metaRows, $headings, $excelRows);
        return response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="configuracion_escala_notas.xlsx"'
        ]);
    }
}
