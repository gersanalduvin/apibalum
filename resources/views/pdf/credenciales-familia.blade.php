<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Credenciales de Familia</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .container {
            width: 100%;
        }

        .card {
            width: 90%;
            margin: 20px auto;
            padding: 30px;
            border-bottom: 1px solid #ccc;
            page-break-inside: avoid;
        }

        .header {
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
        }

        .parent-name {
            font-weight: bold;
            text-transform: uppercase;
        }

        .platform-title {
            text-align: center;
            font-weight: bold;
            margin: 25px 0 15px 0;
            font-size: 16px;
            letter-spacing: 1px;
        }

        .credentials {
            margin-left: 50px;
            margin-bottom: 30px;
        }

        .cred-item {
            margin-bottom: 8px;
            font-size: 14px;
        }

        .cred-label {
            display: inline-block;
            width: 120px;
            text-align: right;
            margin-right: 15px;
            font-weight: normal;
        }

        .cred-value {
            font-weight: bold;
            font-family: 'Courier New', Courier, monospace;
        }

        .footer {
            text-align: center;
            font-style: italic;
            font-size: 12px;
            border-top: 1px solid #000;
            padding-top: 10px;
            margin-top: 20px;
        }

        .whatsapp {
            text-decoration: underline;
            font-weight: bold;
        }

        .group-info {
            text-align: right;
            font-size: 10px;
            color: #000;
            margin-top: -10px;
        }
    </style>
</head>

<body>
    <div class="container">
        @foreach($familias as $familia)
        <div class="card">
            <div class="group-info">
                Grupo: {{ $grupo->nombre }} | Alumnos: <strong>{{ $familia->alumnos_nombres }}</strong>
            </div>

            <div class="header">
                Hola, familia <span class="parent-name">{{ $familia->primer_apellido }}</span>.
                en esta esquela encontrarás los datos de accesos para la Plataforma Académica
                y así puedas mantenerte al tanto de las actividades realizadas en la institución.
            </div>

            <div class="platform-title">
                PLATAFORMA ACADÉMICA
            </div>

            <div class="credentials">
                <div class="cred-item">
                    <span class="cred-label">URL:</span>
                    <span class="cred-value">{{ $url_plataforma }}</span>
                </div>
                <div class="cred-item">
                    <span class="cred-label">USUARIO:</span>
                    <span class="cred-value">{{ $familia->email }}</span>
                </div>
                <div class="cred-item">
                    <span class="cred-label">CONTRASEÑA:</span>
                    <span class="cred-value">{{ $password_default }}</span>
                </div>
            </div>

            <div class="footer">
                ¿Tienes problemas de acceso? Escríbenos por Whatsapp al <span class="whatsapp">505 8252 5780</span>
            </div>
        </div>
        @endforeach
    </div>
</body>

</html>