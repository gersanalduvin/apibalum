<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 6px; text-align: left; }
        th { background: #f0f0f0; }
    </style>
    <title>Áreas de Materias</title>
</head>
<body>
    <h3>Áreas de Materias</h3>
    <table>
        <thead>
            <tr>
                <th>Orden</th>
                <th>Nombre</th>
            </tr>
        </thead>
        <tbody>
        @foreach($rows as $r)
            <tr>
                <td>{{ $r->orden ?? 0 }}</td>
                <td>{{ $r->nombre ?? '' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>

