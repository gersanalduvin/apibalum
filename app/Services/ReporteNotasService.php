<?php

namespace App\Services;

use App\Repositories\Contracts\ReporteNotasRepositoryInterface;
use App\Utils\SimpleXlsxGenerator;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use Illuminate\Support\Facades\View;

class ReporteNotasService
{
    public function __construct(
        private ReporteNotasRepositoryInterface $repository
    ) {}

    public function getReportData(int $grupoId, int $asignaturaId, int $corteId): array
    {
        return $this->repository->getReportData($grupoId, $asignaturaId, $corteId);
    }

    public function generateExcel(int $grupoId, int $asignaturaId, int $corteId): array
    {
        $data = $this->repository->getReportData($grupoId, $asignaturaId, $corteId);

        // Flatten data for Excel
        $rows = [];
        $metadata = $data['metadata'];
        $tasks = $data['tasks'];
        $students = $data['students'];

        // Header Structure
        $headers = ['No.', 'Estudiante'];
        foreach ($tasks as $task) {
            $headers[] = $task['nombre'];
        }

        if (!($metadata['es_iniciativa'] ?? false)) {
            $headers[] = 'Acumulado';
            $headers[] = 'Examen';
            $headers[] = 'Nota Final';
            $headers[] = 'Escala';
        }

        // Data Rows
        $counter = 1;
        $isIniciativa = $metadata['es_iniciativa'] ?? false;

        foreach ($students as $student) {
            $row = [
                'data' => [$counter++, $student['nombre_completo']],
                'style' => 2 // Default bordered
            ];

            foreach ($tasks as $task) {
                $grade = $student['grades'][$task['id']] ?? null;
                
                if ($isIniciativa && $grade) {
                    $config = $grade['indicador_config'] ?? null;
                    $checks = $grade['indicadores_check'] ?? [];
                    $evidenceName = $grade['evidence_name'] ?? $task['nombre'];
                    $text = $evidenceName . " (" . $grade['display'] . ")\n";

                    $criteria = [];
                    if (isset($config['criterios']) && is_array($config['criterios'])) $criteria = $config['criterios'];
                    elseif (isset($config['criterio'])) {
                        $criteria = is_array($config['criterio']) ? array_values($config['criterio']) : [$config['criterio']];
                    }

                    $isSelect = ($config['type'] ?? '') === 'select';
                    if ($isSelect) {
                        // For select type, the badge was hidden in PDF/Web, we also omit the (N/A) if it exists
                        $text = $evidenceName . "\n";
                    }

                    foreach ($criteria as $i => $crit) {
                        $isChecked = $isSelect 
                            ? ($checks['respuesta'] ?? '') === $crit
                            : (!empty($checks[$crit]) || !empty($checks[$i]) || !empty($checks[$i+1]));
                        
                        $symbol = $isSelect ? ($isChecked ? '(X)' : '( )') : ($isChecked ? '[X]' : '[ ]');
                        $text .= $symbol . " " . $crit . "\n";
                    }

                    $row['data'][] = trim($text);
                    // We need a way to specify style PER CELL, but SimpleXlsxGenerator currently only supports style PER ROW.
                    // Actually, I can modify SimpleXlsxGenerator to handle per-cell styles if I really need to, 
                    // or just set the whole row to style 7 (WrapText) since No. and Name don't mind being top-aligned.
                    $row['style'] = 7; 
                } else {
                    $row['data'][] = $grade['display'] ?? '-';
                }
            }

            if (!$isIniciativa) {
                $row['data'][] = $student['acumulado'];
                $row['data'][] = $student['examen'];
                $row['data'][] = $student['nota_final'];
                $row['data'][] = $student['escala'];
            }
            $rows[] = $row;
        }

        // Add metadata to top if desired or just raw data table? 
        // SimpleXlsxGenerator is simple table. Let's add metadata rows first.
        $perfilKey = $isIniciativa ? 'cualitativo' : 'cuantitativo';
        $instNombre = config("institucion.{$perfilKey}.nombre", 'INSTITUTO FRANCISCANO');

        $finalRows = [];
        $finalRows[] = [$instNombre]; // Custom Title
        $finalRows[] = ['Reporte de Notas por Asignatura'];
        $finalRows[] = ['Periodo:', $metadata['periodo'] ?? '', 'Fecha:', date('d/m/Y')];
        $finalRows[] = ['Grupo:', $metadata['grupo'] ?? '', 'Materia:', $metadata['materia'] ?? ''];
        $finalRows[] = ['Docente:', $metadata['docente'] ?? '', 'Corte:', $metadata['corte'] ?? ''];
        $finalRows[] = []; // Empty row
        $finalRows[] = $headers;
        $finalRows = array_merge($finalRows, $rows);

        $bin = SimpleXlsxGenerator::generate([], $finalRows); // Pass empty array as header since we constructed it manually in rows

        $filename = 'notas_' . ($metadata['materia'] ?? 'asignatura') . '_' . date('YmdHis') . '.xlsx';

        return [
            'content' => $bin,
            'filename' => $filename
        ];
    }

    public function generatePdf(int $grupoId, int $asignaturaId, int $corteId)
    {
        $data = $this->repository->getReportData($grupoId, $asignaturaId, $corteId);

        // Determinar perfil institucional basado en el tipo de reporte/grado
        $data['perfil'] = ($data['metadata']['es_iniciativa'] ?? false) ? 'cualitativo' : 'cuantitativo';

        // Generate PDF using a Blade View
        $pdf = PDF::loadView('reportes.notas_asignatura_pdf', $data)
            ->setPaper('a4')
            ->setOrientation('landscape')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)
            ->setOption('margin-right', 10)
            ->setOption('footer-right', 'Página [page] de [toPage]')
            ->setOption('footer-font-size', 8);

        return $pdf->inline('notas_asignatura.pdf');
    }
}
