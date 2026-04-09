<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.5;
        }

        .header-info {
            margin-bottom: 20px;
        }

        .info-grid {
            width: 100%;
            margin-bottom: 15px;
        }

        .info-grid td {
            padding: 5px;
            vertical-align: top;
        }

        .label {
            font-weight: bold;
            color: #666;
            font-size: 10px;
            text-transform: uppercase;
            display: block;
            margin-bottom: 2px;
        }

        .value {
            font-size: 12px;
            color: #000;
        }

        .section-title {
            background-color: #f8f9fa;
            padding: 8px 12px;
            border-left: 4px solid #4a90e2;
            font-weight: bold;
            font-size: 14px;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        .subsection-title {
            font-weight: bold;
            font-size: 12px;
            margin-top: 15px;
            margin-bottom: 5px;
            color: #444;
            border-bottom: 1px solid #eee;
            padding-bottom: 2px;
        }

        .content-box {
            padding: 10px;
            background-color: #fff;
            border: 1px solid #eee;
            border-radius: 4px;
            margin-bottom: 15px;
            white-space: pre-wrap;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        table.data-table td,
        table.data-table th {
            border: 1px solid #eee;
            padding: 8px;
            vertical-align: top;
        }

        table.data-table th {
            background-color: #fcfcfc;
            text-align: left;
            width: 30%;
            font-weight: bold;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
    </style>
</head>

<body>
    <div class="header-info">
        <table class="info-grid">
            <tr>
                <td width="33%">
                    <span class="label">Docente</span>
                    <span class="value">{{ $item->user->name ?? 'N/A' }}</span>
                </td>
                <td width="33%">
                    <span class="label">Asignatura</span>
                    <span class="value">
                        @if($item->is_general)
                        PLAN GENERAL
                        @else
                        {{ $item->asignatura->materia->nombre ?? $item->asignatura->asignatura->nombre ?? 'N/A' }}
                        @endif
                    </span>
                </td>
                <td width="33%">
                    <span class="label">Nivel</span>
                    <span class="value" style="text-transform: capitalize;">{{ $item->nivel }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="label">Fecha</span>
                    <span class="value">
                        {{ \Carbon\Carbon::parse($item->start_date)->format('d/m/Y') }}
                        @if($item->end_date && $item->end_date != $item->start_date)
                        - {{ \Carbon\Carbon::parse($item->end_date)->format('d/m/Y') }}
                        @endif
                    </span>
                </td>
                <td colspan="2">
                    <span class="label">Grupos</span>
                    <span class="value">
                        @if($item->groups && count($item->groups) > 0)
                        {{ implode(', ', array_map(function($g) { return $g['nombre']; }, $item->groups->toArray())) }}
                        @else
                        N/A
                        @endif
                    </span>
                </td>
            </tr>
        </table>
    </div>

    @if($item->nivel != 'primaria')
    <div class="section-title">Información General</div>

    @if(!empty($content['objetivo']))
    <div class="subsection-title">Objetivo / Aprendizaje Esperado</div>
    <div class="content-box">{{ $content['objetivo'] }}</div>
    @endif

    @if(!empty($content['contenido_principal']))
    <div class="subsection-title">Contenido Principal / Tema</div>
    <div class="content-box">{{ $content['contenido_principal'] }}</div>
    @endif

    @if(!empty($content['valor']))
    <div class="subsection-title">Valor</div>
    <div class="content-box">{{ $content['valor'] }}</div>
    @endif

    @if(!empty($content['tema_motivador']))
    <div class="subsection-title">Tema Motivador</div>
    <div class="content-box">{{ $content['tema_motivador'] }}</div>
    @endif
    @endif

    @if(!empty($content['secciones']) && is_array($content['secciones']))
    @foreach($content['secciones'] as $section)
    <div class="section-title">{{ $section['titulo'] }}</div>

    @if(($section['tipo'] ?? '') == 'tabla')
    <table class="data-table">
        @foreach($section['campos'] ?? [] as $campo)
        <tr>
            <th>{{ $campo['nombre'] }}</th>
            <td>{!! nl2br(e($campo['valor'] ?? '')) !!}</td>
        </tr>
        @endforeach
    </table>
    @else
    @foreach($section['campos'] ?? [] as $campo)
    <div class="subsection-title">{{ $campo['nombre'] }}</div>
    <div class="content-box">{!! nl2br(e($campo['valor'] ?? '')) !!}</div>
    @endforeach
    @endif
    @endforeach
    @endif

    <div class="footer">
        Este documento es un registro oficial de planificación académica generado el {{ now()->format('d/m/Y H:i') }}.
    </div>
</body>

</html>