<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Boletín Escolar - {{ $grupo->grado->nombre }} {{ $grupo->seccion->nombre }}</title>    <style>
        @page {
            margin: 0.5cm;
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
            line-height: 1.1;
            color: #000;
        }

        .border-outer {
            border: 2pt solid #006400; /* Dark Green border */
            padding: 3px;
            width: 100%;
            height: 262mm; /* Reduced to fit Letter page safely */
            box-sizing: border-box;
            background-color: #fff;
            margin: 0 auto;
        }

        .page-wrapper {
            border: 1.5pt solid #0000ff; /* Blue border */
            padding: 8px;
            width: 100%;
            height: 100%; /* Fill the outer container */
            position: relative;
            box-sizing: border-box;
            background-color: #fff;
            overflow: hidden;
        }

        .page-break {
            page-break-after: always;
        }

        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .main-table th,
        .main-table td {
            border: 1px solid #000;
            padding: 4px 1px;
            text-align: center;
            font-size: 7.5pt;
            vertical-align: middle;
        }

        .main-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .area-row {
            background-color: #e8e8e8;
            font-weight: bold;
            text-align: left;
            font-size: 7.5pt;
        }

        .asignatura-row td:first-child {
            text-align: left;
            padding-left: 5px;
        }

        .footer-signatures {
            position: absolute;
            bottom: 60px;
            left: 20px;
            right: 20px;
            width: 95%;
        }

        .signature-line {
            width: 40%;
            border-top: 1px solid #000;
            text-align: center;
            padding-top: 5px;
            font-size: 10pt;
        }
    </style>
</head>

<body>
    @foreach($estudiantes as $index => $estudianteData)
    {{-- PAGE 1: FRONT (GRADES) --}}
    <div class="border-outer">
        <div class="page-wrapper">
        {{-- Calificaciones --}}
        @include('pdf.partials.boletin-calificaciones', [
        'estudiante' => $estudianteData['estudiante'],
        'calificaciones' => $estudianteData['calificaciones'],
        'grupo' => $grupo,
        'corte_id_filtro' => $corte_id_filtro ?? null,
        'corte_orden_filtro' => $corte_orden_filtro ?? null
        ])

        {{-- Observations Section --}}
        <div style="margin-top: 20px; padding: 0 10px;">
            <div style="font-weight: bold; font-size: 10pt; border-bottom: 1px solid #000; padding-bottom: 2px; margin-bottom: 5px; display: inline-block;">OBSERVACIÓN DEL CORTE</div>
            <div style="font-size: 10pt; min-height: 35px; padding: 5px; border: 1px dashed #ccc; margin-top: 5px;">
                {{ $estudianteData['observacion'] ?? '' }}
            </div>
        </div>

        {{-- Signatures Section --}}
        <div class="footer-signatures">
            <table style="width: 100%; border: none;">
                <tr style="border: none;">
                    <td style="border: none; width: 45%; vertical-align: top;">
                        <div style="border-top: 1px solid #000; margin-top: 15px; padding-top: 5px; text-align: center;">
                            <strong>FIRMA DEL DOCENTE</strong>
                        </div>
                    </td>
                    <td style="border: none; width: 10%;"></td>
                    <td style="border: none; width: 45%; vertical-align: top;">
                        <div style="border-top: 1px solid #000; margin-top: 15px; padding-top: 5px; text-align: center;">
                            <strong>{{ config('institucion.cuantitativo.director') }}</strong><br>
                            Directora
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        </div>
    </div>

    <div class="page-break"></div>

    {{-- PAGE 2: BACK (HISTORY) --}}
    <div class="border-outer">
        <div class="page-wrapper">
            @include('pdf.partials.boletin-trasera')
        </div>
    </div>

    @if($index < count($estudiantes) - 1)
        <div class="page-break"></div>
        @endif
    @endforeach
</body>

</html>