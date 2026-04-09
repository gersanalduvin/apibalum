<?php

namespace App\Services;

use App\Repositories\NotMateriaRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Barryvdh\Snappy\Facades\SnappyPdf;
use App\Utils\SimpleXlsxGenerator;

class NotMateriaService
{
    public function __construct(private NotMateriaRepository $repository) {}

    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getPaginated($filters, $perPage);
    }

    public function find(int $id)
    {
        return $this->repository->find($id);
    }

    public function create(array $data)
    {
        return DB::transaction(fn() => $this->repository->create($data));
    }

    public function update(int $id, array $data)
    {
        return DB::transaction(fn() => $this->repository->update($id, $data));
    }

    public function delete(int $id): bool
    {
        return DB::transaction(fn() => $this->repository->delete($id));
    }

    public function exportPdf(array $filters = [])
    {
        $rows = $this->repository->getPaginated($filters, 1000)->items();
        $html = view('pdf.not-materias', ['rows' => $rows, 'filters' => $filters])->render();
        $titulo = 'MATERIAS';
        $subtitulo1 = '';
        $subtitulo2 = '';
        $nombreInstitucion = config('app.nombre_institucion', 'INSTITUCIÓN');
        $headerHtml = view()->make('pdf.header', compact('titulo','subtitulo1','subtitulo2','nombreInstitucion'))->render();
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
        $nombreArchivo = 'materias_'.now()->format('Y-m-d_H-i-s').'.pdf';
        return $pdf->download($nombreArchivo);
    }

    public function exportExcel(array $filters = [])
    {
        $rows = $this->repository->getPaginated($filters, 1000)->items();
        $headings = ['Nombre','Abreviatura'];
        $excelRows = array_map(fn($r) => [
            $r->nombre ?? '',
            $r->abreviatura ?? ''
        ], $rows);
        $metaRows = [
            ['Reporte','Materias'],
            [],
        ];
        $binary = SimpleXlsxGenerator::generateWithMeta($metaRows, $headings, $excelRows);
        return response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="materias.xlsx"'
        ]);
    }
}
