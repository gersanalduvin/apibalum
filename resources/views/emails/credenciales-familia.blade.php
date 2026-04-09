<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f4f6f8;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 650px;
            margin: 0 auto;
            background: #ffffff;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            border-top: 6px solid #4f46e5;
        }

        .header {
            text-align: center;
            padding-bottom: 25px;
            margin-bottom: 30px;
            border-bottom: 1px solid #e5e7eb;
        }

        .header h1 {
            margin: 0;
            color: #1f2937;
            font-size: 26px;
            letter-spacing: -0.5px;
        }

        .welcome {
            font-size: 16px;
            margin-bottom: 30px;
            color: #4b5563;
        }

        .credentials-box {
            background-color: #f3f4f6;
            border-left: 4px solid #4f46e5;
            padding: 20px 25px;
            margin-bottom: 30px;
            border-radius: 6px;
        }

        .credentials-box h3 {
            margin-top: 0;
            color: #3730a3;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .credential-item {
            margin-bottom: 12px;
            font-size: 16px;
            display: flex;
            align-items: center;
        }

        .credential-label {
            font-weight: 600;
            color: #4b5563;
            width: 110px;
            flex-shrink: 0;
        }

        .credential-value {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 16px;
            color: #111827;
            background: #ffffff;
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
            flex-grow: 1;
            max-width: fit-content;
        }

        .platforms-section {
            margin-top: 35px;
            padding: 25px;
            background-color: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .platforms-section h3 {
            margin-top: 0;
            color: #1e293b;
            font-size: 18px;
            text-align: center;
            margin-bottom: 20px;
        }

        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .btn {
            display: block;
            text-align: center;
            text-decoration: none;
            padding: 14px 25px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.2s ease;
        }

        .btn-web {
            background-color: #4f46e5;
            color: #ffffff;
            border: 1px solid #4338ca;
        }

        .btn-web:hover {
            background-color: #4338ca;
        }

        .btn-android {
            background-color: #10b981;
            color: #ffffff;
            border: 1px solid #059669;
        }

        .btn-ios {
            background-color: #f1f5f9;
            color: #64748b;
            border: 1px solid #cbd5e1;
            cursor: not-allowed;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }

        .badge {
            background: #e2e8f0;
            color: #475569;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .students-section {
            margin-top: 35px;
        }

        .students-section h3 {
            font-size: 18px;
            color: #374151;
            border-bottom: 2px solid #f3f4f6;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .student-item {
            padding: 12px 0;
            display: flex;
            align-items: center;
            color: #4b5563;
        }

        .student-item:not(:last-child) {
            border-bottom: 1px solid #f3f4f6;
        }

        .student-icon {
            color: #10b981;
            margin-right: 12px;
            font-size: 18px;
            background: #ecfdf5;
            height: 28px;
            width: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .footer {
            text-align: center;
            font-size: 13px;
            color: #6b7280;
            margin-top: 40px;
            padding-top: 25px;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Centro Escolar Mis Primeros Pasos</h1>
        </div>

        <div class="welcome">
            <p>Estimada familia <strong>{{ $familia->primer_nombre }} {{ $familia->primer_apellido }}</strong>,</p>
            <p>Es un placer saludarles de parte del <strong>Centro Escolar Mis Primeros Pasos</strong>. Hemos generado y configurado sus nuevas credenciales de acceso para nuestra plataforma educativa institucional (GNUBE).</p>
            <p>A través de esta plataforma podrán dar seguimiento al desarrollo académico, agenda escolar y notificaciones institucionales de sus estudiantes vinculados.</p>
        </div>

        <div class="credentials-box">
            <h3>🔐 Sus credenciales seguras</h3>
            <div class="credential-item">
                <span class="credential-label">Usuario:</span>
                <span class="credential-value">{{ $familia->email }}</span>
            </div>
            <div class="credential-item">
                <span class="credential-label">Contraseña:</span>
                <span class="credential-value">{{ $passwordPlano }}</span>
            </div>
        </div>

        <div class="platforms-section">
            <h3>📱 ¿Desde dónde desea acceder?</h3>
            <p style="text-align: center; margin-bottom: 25px; color: #64748b; font-size: 14px;">Elija la plataforma de su preferencia para ingresar al sistema:</p>

            <div class="btn-group">
                <a href="https://misprimerospasos.gnube.app/" class="btn btn-web" style="color: #ffffff; text-decoration: none;">
                    💻 Acceder desde el Navegador Web
                </a>
                <a href="https://play.google.com/store/apps/details?id=com.gsoftnic.gnube_primerospasos" class="btn btn-android" style="color: #ffffff; text-decoration: none;">
                    ▶️ Descargar App en Google Play (Android)
                </a>
                <a href="https://apps.apple.com/us/app/primeros-pasos-le%C3%B3n/id6759716027" class="btn btn-android" style="color: #ffffff; text-decoration: none; background-color: #000000; border: 1px solid #333333;">
                    🍎 Descargar App en App Store (iOS)
                </a>
            </div>
        </div>

        @if(count($hijos) > 0)
        <div class="students-section">
            <h3>🎓 Estudiantes Vinculados</h3>
            @foreach($hijos as $hijo)
            <div class="student-item">
                <div class="student-icon">✓</div>
                <div>
                    <strong>{{ $hijo->primer_nombre }} {{ $hijo->primer_apellido }} @if($hijo->segundo_apellido) {{ $hijo->segundo_apellido }} @endif</strong>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <div class="footer">
            <p><em>Por seguridad, le sugerimos cambiar su contraseña temporal una vez haya ingresado por primera vez al portal o la aplicación.</em></p>
            <p>Este es un correo automatizado de servicios escolares. Por favor no responda a este mensaje.</p>
            <p>&copy; {{ date('Y') }} Centro Escolar Mis Primeros Pasos. Desarrollo por GNUBE.</p>
        </div>
    </div>
</body>

</html>