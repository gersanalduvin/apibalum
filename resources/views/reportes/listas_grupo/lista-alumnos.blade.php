<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Lista de Alumnos por Grupo</title>
    <style>
        .tabla {
            width: 100%;
            border-collapse: collapse;
        }

        .tabla th,
        .tabla td {
            border: 1px solid #333;
            padding: 6px;
            font-size: 14px;
        }

        .encabezado {
            margin-bottom: 10px;
            font-size: 14px;
        }
    </style>
    <link rel="stylesheet" href="{{ public_path('css/pdf-global-styles.css') }}">
</head>

<body>
    @foreach($reporteData as $index => $data)
    @php
    $grupo = $data['grupo'];
    $alumnos = $data['alumnos'];
    $femenino = $data['femenino'];
    $masculino = $data['masculino'];
    $total_alumnos = $data['total_alumnos'];
    @endphp

    <div style="{{ $index > 0 ? 'page-break-before: always;' : '' }}">
        @include('pdf.header_content', [
            'perfil' => ($grupo->formato ?? 'cuantitativo') === 'cualitativo' ? 'cualitativo' : 'cuantitativo',
            'titulo' => 'LISTA DE ALUMNOS POR GRUPO',
            'subtitulo1' => 'Período: ' . ($periodo->nombre ?? '')
        ])

        <!-- Group Header (Only explicitly needed if not in main Header, but here we add specific Group details) -->
        <!-- The main Header has generic info for "Todos". We need clear Group identification on each page. -->
        <div class="encabezado" style="margin-bottom: 5px;">
            <table style="width: 100%; border: none;">
                <tr>
                    <td style="border: none; width: 60%;">
                        <strong>Grupo:</strong> {{ ($grupo->grado ?? '') }} - {{ ($grupo->seccion ?? '') }} ({{ ($grupo->turno ?? '') }})
                    </td>
                    <td style="border: none; width: 40%; text-align: right;">
                        <strong>Docente Guía:</strong> {{ $grupo->docente_guia_nombre ?? 'N/A' }}
                    </td>
                </tr>
            </table>
        </div>

        <table class="tabla">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 50%;">Nombre Completo</th>
                    <th style="width: 35%;">Correo</th>
                    <th style="width: 10%;">Sexo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($alumnos as $idx => $alumno)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $alumno['nombre_completo'] }}</td>
                    <td>{{ $alumno['correo'] ?? '' }}</td>
                    <td>{{ $alumno['sexo'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <br>
        <table class="tabla" style="width: 200px;">
            <thead>
                <tr>
                    <th colspan="3">Resumen por sexo</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Femenino</td>
                    <td>{{ $femenino }}</td>
                </tr>
                <tr>
                    <td>Masculino</td>
                    <td>{{ $masculino }}</td>
                </tr>
                <tr>
                    <td>Total</td>
                    <td>{{ $total_alumnos }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endforeach
</body>

</html>