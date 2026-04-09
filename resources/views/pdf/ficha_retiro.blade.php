<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Ficha de Retiro</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .title {
            font-size: 16px;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 5px;
        }

        .section {
            margin-bottom: 20px;
            border: 1px solid #000;
            padding: 10px;
        }

        .section-title {
            font-weight: bold;
            background-color: #eee;
            padding: 5px;
            border-bottom: 1px solid #000;
            margin: -10px -10px 10px -10px;
        }

        .row {
            display: table;
            width: 100%;
            margin-bottom: 5px;
        }

        .col {
            display: table-cell;
            padding-right: 10px;
        }

        .label {
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 5px;
            text-align: left;
        }

        th {
            background-color: #f9f9f9;
        }

        .signatures {
            margin-top: 100px;
            width: 100%;
        }

        .signature-box {
            display: inline-block;
            width: 45%;
            text-align: center;
            border-top: 1px solid #000;
            margin: 0 2%;
            padding-top: 5px;
        }
    </style>
</head>

<body>
    @include('pdf.header', [
    'nombreInstitucion' => $nombreInstitucion,
    'titulo' => 'FICHA DE RETIRO',
    'subtitulo1' => 'Fecha de Impresión: ' . now()->format('d/m/Y H:i A')
    ])

    <!-- Datos del Alumno -->
    <div class="section">
        <div class="section-title">DATOS DEL ALUMNO</div>
        <div class="row">
            <div class="col"><span class="label">Nombre:</span> {{ $user->primer_nombre }} {{ $user->segundo_nombre }} {{ $user->primer_apellido }} {{ $user->segundo_apellido }}</div>
            <div class="col"><span class="label">Código MINED:</span> {{ $user->codigo_mined }}</div>
        </div>
        <div class="row">
            <div class="col"><span class="label">Código Único:</span> {{ $user->codigo_unico }}</div>
            <div class="col"><span class="label">Fecha Nacimiento:</span> {{ $user->fecha_nacimiento ? \Carbon\Carbon::parse($user->fecha_nacimiento)->format('d/m/Y') : 'N/A' }}</div>
            <div class="col"><span class="label">Edad:</span> {{ $user->edad }} años</div>
        </div>
    </div>

    <!-- Información de Retiro -->
    <div class="section">
        <div class="section-title">INFORMACIÓN DE RETIRO</div>
        <div class="row">
            <div class="col"><span class="label">Fecha de Retiro:</span> {{ $user->fecha_retiro ? \Carbon\Carbon::parse($user->fecha_retiro)->format('d/m/Y') : 'No registrado' }}</div>
            <div class="col"><span class="label">Notificado por padres:</span> {{ $user->retiro_notificado ? 'SÍ' : 'NO' }}</div>
        </div>
        <div class="row">
            <div class="col"><span class="label">Motivo:</span><br> {{ $user->motivo_retiro ?? 'N/A' }}</div>
        </div>
        <div class="row">
            <div class="col"><span class="label">Observaciones/Información Adicional:</span><br> {{ $user->informacion_retiro_adicional ?? 'Ninguna' }}</div>
        </div>
    </div>

    <!-- Registro Académico (Últimos grupos) -->
    <div class="section">
        <div class="section-title">REGISTRO ACADÉMICO (HISTORIAL)</div>
        @if($user->grupos && $user->grupos->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Período</th>
                    <th>Grado/Nivel</th>
                    <th>Grupo/Sección</th>
                    <th>Turno</th>
                    <th>Fecha Inscripción</th>
                </tr>
            </thead>
            <tbody>
                @foreach($user->grupos->sortByDesc('created_at') as $ug)
                @if($ug->periodoLectivo && $ug->periodoLectivo->periodo_nota == 1)
                <tr>
                    <td>{{ $ug->periodoLectivo->nombre ?? 'N/A' }}</td>
                    <td>{{ $ug->grado->nombre ?? 'N/A' }}</td>
                    <td>{{ $ug->grupo->nombre ?? 'Sin grupo' }}</td>
                    <td>{{ $ug->turno->nombre ?? 'N/A' }}</td>
                    <td>{{ $ug->created_at->format('d/m/Y') }}</td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
        @else
        <div>No hay registro de grupos asignados.</div>
        @endif
    </div>

    <div class="signatures">
        <div class="signature-box">
            Firma Director(a)
        </div>
        <div class="signature-box">
            Firma Padre/Madre/Tutor
        </div>
    </div>
</body>

</html>