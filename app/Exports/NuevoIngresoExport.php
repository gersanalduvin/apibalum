<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class NuevoIngresoExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'codigo_unico',
            'primer_nombre',
            'segundo_nombre',
            'primer_apellido',
            'segundo_apellido',
            'fecha_nacimiento',
            'sexo',
            'lugar_nacimiento',
            'nombre_madre',
            'cedula_madre',
            'telefono_tigo_madre',
            'telefono_claro_madre',
            'nombre_padre',
            'cedula_padre',
            'telefono_tigo_padre',
            'nombre_responsable',
            'cedula_responsable',
            'fecha_matricula',
            'grado',
            'modalidad',
            'turno',
        ];
    }

    public function map($row): array
    {
        $fechaNacimiento = $row->fecha_nacimiento ? \Carbon\Carbon::parse($row->fecha_nacimiento)->format('d/m/Y') : null;
        $fechaMatricula = $row->fecha_matricula ? \Carbon\Carbon::parse($row->fecha_matricula)->format('d/m/Y') : null;

        return [
            $row->codigo_unico,
            $row->primer_nombre,
            $row->segundo_nombre,
            $row->primer_apellido,
            $row->segundo_apellido,
            $fechaNacimiento,
            $row->sexo,
            $row->lugar_nacimiento,
            $row->nombre_madre,
            $row->cedula_madre,
            $row->telefono_tigo_madre,
            $row->telefono_claro_madre,
            $row->nombre_padre,
            $row->cedula_padre,
            $row->telefono_tigo_padre,
            $row->nombre_responsable,
            $row->cedula_responsable,
            $fechaMatricula,
            $row->grado,
            $row->modalidad,
            $row->turno,
        ];
    }
}