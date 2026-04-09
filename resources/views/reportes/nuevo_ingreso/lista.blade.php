<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 0.25in; }
        body { font-family: 'Segoe UI', Arial, Helvetica, sans-serif; font-size: 9pt; line-height: 1.2; letter-spacing: 0.2px; }
        h1 { font-size: 14pt; margin: 0 0 8px 0; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        th, td { border: 1px solid #333; padding: 2px; vertical-align: top; overflow: hidden; }
        th { background: #f0f0f0; text-align: left; -webkit-print-color-adjust: exact; }
        thead th { white-space: nowrap; font-weight: bold; }
        tr, td, th { page-break-inside: avoid; }
        .nowrap { white-space: nowrap; }
        .wrap { white-space:normal ; word-break: break-word; overflow-wrap: break-word; }
    </style>
    <title>{{ $titulo }}</title>
    </head>
<body>
    <h1>{{ $titulo }}</h1>
    <table>
        <colgroup>
            <col style="width:4.7%"> <!-- COD -->
            <col style="width:4.7%"> <!-- P.N -->
            <col style="width:4.7%"> <!-- S.N -->
            <col style="width:4.7%"> <!-- P.A -->
            <col style="width:4.7%"> <!-- S.A -->
            <col style="width:4.7%"> <!-- F.NAC -->
            <col style="width:3%"> <!-- Sexo -->
            <col style="width:4.7%"> <!-- Lugar Nac -->
            <col style="width:4.7%"> <!-- N.MA -->
            <col style="width:4.7%"> <!-- CED.MA -->
            <col style="width:4.7%"> <!-- T.TIGO -->
            <col style="width:4.7%"> <!-- T.CLARO -->
            <col style="width:4.7%"> <!-- Dirección madre -->
            <col style="width:4.7%"> <!-- Nombre padre -->
            <col style="width:4.7%"> <!-- Cédula padre -->
            <col style="width:4.7%"> <!-- Tel. Tigo padre -->
            <col style="width:4.7%"> <!-- Dirección padre -->
            <col style="width:4.7%"> <!-- Fecha matrícula -->
            <col style="width:4.7%"> <!-- Grado -->
            <col style="width:4.7%"> <!-- Modalidad -->
            <col style="width:4.7%"> <!-- Turno -->

        </colgroup>

        <thead>
            <tr>
                <th class="wrap">COD</th>
                <th class="wrap">P.N</th>
                <th class="wrap">S.N</th>
                <th class="wrap">P.A</th>
                <th class="wrap">S.A</th>
                <th class="wrap">F.NAC</th>
                <th class="wrap">Sexo</th>
                <th class="wrap">Lugar Nac</th>
                <th class="wrap">N.MA</th>
                <th class="wrap">CED.MA</th>
                <th class="wrap">T.TIGO</th>
                <th class="wrap">T.CLARO</th>
                <th class="wrap">Dirección madre</th>
                <th class="wrap">Nombre padre</th>
                <th class="wrap">Cédula padre</th>
                <th class="wrap">Tel. Tigo padre</th>
                <th class="wrap">Dirección padre</th>
                <th class="wrap">Fecha matrícula</th>
                <th class="wrap">Grado</th>
                <th class="wrap">Modalidad</th>
                <th class="wrap">Turno</th>
            </tr>
        </thead>

        <tbody>
        @foreach ($rows as $row)
            <tr>
                <td class="wrap">{{ $row->codigo_unico }}</td>
                <td class="wrap">{{ $row->primer_nombre }}</td>
                <td class="wrap">{{ $row->segundo_nombre }}</td>
                <td class="wrap">{{ $row->primer_apellido }}</td>
                <td class="wrap">{{ $row->segundo_apellido }}</td>
                <td class="wrap">{{ $row->fecha_nacimiento ? \Carbon\Carbon::parse($row->fecha_nacimiento)->format('d/m/Y') : '' }}</td>
                <td class="wrap">{{ $row->sexo }}</td>
                <td class="wrap">{{ $row->lugar_nacimiento }}</td>
                <td class="wrap">{{ $row->nombre_madre }}</td>
                <td class="wrap">{{ $row->cedula_madre }}</td>
                <td class="wrap">{{ $row->telefono_tigo_madre }}</td>
                <td class="wrap">{{ $row->telefono_claro_madre }}</td>
                <td class="wrap">{{ $row->direccion_madre }}</td>
                <td class="wrap">{{ $row->nombre_padre }}</td>
                <td class="wrap">{{ $row->cedula_padre }}</td>
                <td class="wrap">{{ $row->telefono_tigo_padre }}</td>
                <td class="wrap">{{ $row->direccion_padre }}</td>
                <td class="wrap">{{ $row->fecha_matricula ? \Carbon\Carbon::parse($row->fecha_matricula)->format('d/m/Y') : '' }}</td>
                <td class="wrap">{{ $row->grado }}</td>
                <td class="wrap">{{ $row->modalidad }}</td>
                <td class="wrap">{{ $row->turno }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
