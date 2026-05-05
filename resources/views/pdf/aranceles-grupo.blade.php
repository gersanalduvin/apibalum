<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Aranceles por Grupo</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }

        .student-info {
            margin-bottom: 20px;
            border: 1px solid #000;
            padding: 10px;
            background-color: #f5f5f5;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px 4px;
            text-align: left;
            word-wrap: break-word;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
            font-size: 10px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .totals-table {
            float: right;
            width: 250px;
            margin-top: 15px;
        }

        .totals-table td {
            border: 1px solid #000;
        }

        .grand-total {
            font-weight: bold;
            background-color: #eee;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
    @foreach($students as $index => $student)
        <div class="{{ $index < count($students) - 1 ? 'page-break' : '' }}">
            @include('pdf.aranceles-usuario-partial', [
                'user' => $student['user'],
                'aranceles' => $student['aranceles'],
                'grado' => $student['grado'],
                'grupo' => $student['grupo']
            ])
        </div>
    @endforeach
</body>

</html>
