<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 11px;
        }

        .page-break {
            page-break-after: always;
        }

        .container {
            width: 100%;
            margin-bottom: 20px;
        }

        h1 {
            text-align: center;
            font-size: 16px;
            margin-bottom: 2px;
            text-transform: uppercase;
        }

        h2 {
            text-align: center;
            font-size: 12px;
            margin-top: 0;
            color: #666;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            /* Ensures equal width columns */
        }

        th {
            border: 1px solid #444;
            padding: 8px;
            text-align: center;
            background-color: #eee;
            font-weight: bold;
            font-size: 12px;
        }

        td {
            border: 1px solid #444;
            padding: 4px;
            vertical-align: top;
            background-color: #fff;
        }

        .class-item {
            border: 1px solid #ccc;
            background-color: #f9f9f9;
            padding: 5px;
            margin-bottom: 6px;
            border-radius: 4px;
        }

        .class-time {
            font-size: 10px;
            font-weight: bold;
            color: #d32f2f;
            /* Highlight time */
            display: block;
            margin-bottom: 2px;
            border-bottom: 1px dotted #ddd;
            padding-bottom: 2px;
        }

        .subject {
            font-weight: bold;
            color: #000;
            font-size: 11px;
            display: block;
        }

        .detail {
            font-size: 9px;
            color: #555;
            display: block;
            margin-top: 1px;
        }

        .empty-day {
            text-align: center;
            color: #999;
            font-style: italic;
            padding: 10px;
        }
    </style>
</head>

<body>
    @foreach($items as $index => $item)
    <div class="container @if(!$loop->last) page-break @endif">
        @include('pdf.header', [
        'nombreInstitucion' => $nombreInstitucion,
        'titulo' => 'HORARIO DE CLASES',
        'subtitulo1' => $item['title'],
        'subtitulo2' => $item['subtitle']
        ])

        <table>
            <thead>
                <tr>
                    <th style="width: 20%;">Lunes</th>
                    <th style="width: 20%;">Martes</th>
                    <th style="width: 20%;">Miércoles</th>
                    <th style="width: 20%;">Jueves</th>
                    <th style="width: 20%;">Viernes</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    @for($dia = 1; $dia <= 5; $dia++)
                        <td>
                        @php
                        // Filter and sort classes for this day
                        $clases = $item['schedule']->filter(function($c) use ($dia) {
                        return $c->dia_semana == $dia;
                        })->sortBy(function($c) {
                        return $c->hora_inicio_real ?? $c->bloqueHorario->hora_inicio ?? '99:99';
                        });
                        @endphp

                        @if($clases->isEmpty())
                        <div class="empty-day">-</div>
                        @else
                        @foreach($clases as $clase)
                        <div class="class-item">
                            <span class="class-time">
                                {{ Substr($clase->hora_inicio_real ?? $clase->bloqueHorario->hora_inicio ?? '', 0, 5) }} -
                                {{ Substr($clase->hora_fin_real ?? $clase->bloqueHorario->hora_fin ?? '', 0, 5) }}
                            </span>

                            <span class="subject">
                                {{ $clase->titulo_personalizado 
                                            ?? ($clase->asignaturaGrado->materia->nombre ?? $clase->asignaturaGrado->materia->abreviatura ?? 'Asig. Indefinida') 
                                        }}
                            </span>

                            @if(isset($clase->grupo) && !in_array($type, ['grupo', 'todos_grupos']))
                            <span class="detail">
                                Grp: {{ $clase->grupo->nombre ?? ($clase->grupo->grado->abreviatura ?? '') . ' ' . ($clase->grupo->seccion->nombre ?? '') }}
                            </span>
                            @endif

                            @if(isset($clase->docente) && !in_array($type, ['docente', 'todos_docentes']))
                            <span class="detail">
                                Prof. {{ $clase->docente->name ?? $clase->docente->primer_nombre }}
                            </span>
                            @endif

                            @if(isset($clase->aula) && $type !== 'aula')
                            <span class="detail">
                                Aula: {{ $clase->aula->nombre }}
                            </span>
                            @endif
                        </div>
                        @endforeach
                        @endif
                        </td>
                        @endfor
                </tr>
            </tbody>
        </table>
    </div>
    @endforeach
</body>

</html>