<?php

namespace App\Services;

use App\Repositories\Contracts\StudentExportRepositoryInterface;
use App\Utils\SimpleXlsxGenerator;
use Carbon\Carbon;

class StudentExportService
{
    protected $repository;

    public function __construct(StudentExportRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Generate Excel export for students.
     *
     * @param int $periodoId
     * @param array $requestedFields
     * @return array
     */
    public function generateExport(int $periodoId, array $requestedFields): array
    {
        $students = $this->repository->getStudentsByPeriod($periodoId);

        $rows = [];
        foreach ($students as $student) {
            $row = [];
            foreach ($requestedFields as $fieldKey) {
                if ($fieldKey === 'nombre_completo') {
                    $row[] = trim("$student->primer_nombre $student->segundo_nombre $student->primer_apellido $student->segundo_apellido");
                } elseif ($fieldKey === 'edad') {
                    $row[] = $student->fecha_nacimiento ? Carbon::parse($student->fecha_nacimiento)->age : '';
                } elseif ($fieldKey === 'grado') {
                    $row[] = $student->grado_nombre ?? '';
                } elseif ($fieldKey === 'seccion') {
                    $row[] = $student->seccion_nombre ?? '';
                } elseif ($fieldKey === 'turno') {
                    $row[] = $student->turno_nombre ?? '';
                } elseif ($fieldKey === 'periodo_lectivo') {
                    $row[] = $student->periodo_nombre ?? '';
                } elseif ($fieldKey === 'numero_recibo') {
                    $row[] = $student->numero_recibo ?? '';
                } elseif ($fieldKey === 'fecha_matricula') {
                    $row[] = $student->fecha_matricula ? Carbon::parse($student->fecha_matricula)->format('d-m-Y') : '';
                } else {
                    $row[] = $student->{$fieldKey} ?? '';
                }
            }
            $rows[] = $row;
        }

        $headings = array_map(function ($f) {
            return ucwords(str_replace('_', ' ', $f));
        }, $requestedFields);

        $binary = SimpleXlsxGenerator::generate($headings, $rows);
        $fileName = 'alumnos_export_' . date('Y-m-d_H-i-s') . '.xlsx';

        return [
            'content' => $binary,
            'filename' => $fileName
        ];
    }
}
