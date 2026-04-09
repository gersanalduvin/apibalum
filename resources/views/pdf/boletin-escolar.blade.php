<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Boletín Escolar - {{ $grupo->grado->nombre }} {{ $grupo->seccion->nombre }}</title>
    <style>
        @page {
            margin: 1cm;
        }

        body,
        h1,
        h2,
        h3,
        h4,
        p,
        div,
        span,
        td,
        th {
            font-family: Verdana, sans-serif;
            word-wrap: break-word;
        }

        body {
            font-size: 11pt !important;
            line-height: 1.2;
            color: #000;
        }

        .page-break {
            page-break-after: always;
        }

        .portada {
            text-align: center;
            padding: 20px;
        }

        .portada h1 {
            font-size: 14px;
            margin: 5px 0;
        }

        .portada h2 {
            font-size: 12px;
            margin: 5px 0;
        }

        .portada .logo {
            margin: 20px 0;
        }

        .estudiante-info {
            margin: 30px 0 50px 50px;
            text-align: left;

        }

        .estudiante-info p {
            margin: 5px 0;
            font-size: 11px;
        }

        .padre-mio {
            margin: 30px 20px;
            text-align: center;
            font-size: 12px;
            line-height: 1.4;
        }

        .padre-mio h3 {
            font-size: 11px;
            margin-bottom: 10px;
        }

        .deberes {
            margin: 20px;
            text-align: left !important;
            font-size: 10px;

        }

        .deberes h4 {
            font-size: 12px;
            margin-bottom: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 3px 2px;
            text-align: center;
            font-size: 9px;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .area-row {
            background-color: #e8e8e8;
            font-weight: bold;
            text-align: center;
            font-size: 8px;
        }

        .asignatura-row td:first-child {
            text-align: left;
            padding-left: 5px;
        }
    </style>
</head>

<body>
    @foreach($estudiantes as $index => $estudianteData)
    {{-- Calificaciones --}}
    @include('pdf.partials.boletin-calificaciones', [
    'estudiante' => $estudianteData['estudiante'],
    'calificaciones' => $estudianteData['calificaciones'],
    'grupo' => $grupo,
    'corte_id_filtro' => $corte_id_filtro ?? null,
    'corte_orden_filtro' => $corte_orden_filtro ?? null
    ])

    <div class="footer-section" style="margin-top: 40px; font-size: 10px; page-break-inside: avoid;">
        <div style="width: 250px; border-top: 1px solid #000; margin-bottom: 5px;"></div>
        <div>Profesora: {{ $grupo->docenteGuia->primer_nombre ?? '' }} {{ $grupo->docenteGuia->primer_apellido ?? '' }}</div>

        <table style="width: 100%; border: none; margin-top: 15px;">
            <tr style="border: none;">
                <!-- Escala de Calificación -->
                <td style="width: 45%; vertical-align: top; border: none; padding: 0;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th colspan="2">Escala de Calificación</th>
                            </tr>
                            <tr>
                                <th colspan="2">Nivel de Competencias Alcanzadas</th>
                            </tr>
                            <tr>
                                <th>Cualitativo</th>
                                <th>Cuantitativo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="text-align: left;">Aprendizaje Avanzado (AA)</td>
                                <td>90 - 100</td>
                            </tr>
                            <tr>
                                <td style="text-align: left;">Aprendizaje Satisfactorio (AS)</td>
                                <td>76 - 89</td>
                            </tr>
                            <tr>
                                <td style="text-align: left;">Aprendizaje Fundamental (AF)</td>
                                <td>60 - 75</td>
                            </tr>
                            <tr>
                                <td style="text-align: left;">Aprendizaje Inicial (AI)</td>
                                <td>menos de 59</td>
                            </tr>
                        </tbody>
                    </table>
                    <div style="font-weight: bold; margin-top: 5px; text-align: left;">Nota mínima para aprobar 60.</div>
                </td>

                <!-- Spacer -->
                <td style="width: 5%; border: none;"></td>

                <!-- Observaciones Lines -->
                <td style="width: 50%; vertical-align: top; border: none; padding: 0;">
                    <div style="font-weight: bold; margin-bottom: 5px; text-align: left;">Observaciones:</div>
                    @php
                    // Si hay observaciones guardadas, lo mostramos en la primera línea.
                    $obsText = $estudianteData['observacion'] ?? '';
                    @endphp
                    <div style="border-bottom: 1px solid #000; height: 16px; margin-bottom: 8px; text-align: left;">
                        <span style="font-size: 10px; padding-left: 2px;">{{ $obsText }}</span>
                    </div>
                    <div style="border-bottom: 1px solid #000; height: 16px; margin-bottom: 8px;"></div>
                    <div style="border-bottom: 1px solid #000; height: 16px; margin-bottom: 8px;"></div>
                    <div style="border-bottom: 1px solid #000; height: 16px; margin-bottom: 8px;"></div>
                    <div style="border-bottom: 1px solid #000; height: 16px; margin-bottom: 8px;"></div>
                </td>
            </tr>
        </table>
    </div>

    @if($index < count($estudiantes) - 1)
        <div class="page-break">
        </div>
        @endif
        @endforeach
</body>

</html>