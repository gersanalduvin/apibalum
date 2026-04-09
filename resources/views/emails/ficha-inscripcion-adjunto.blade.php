<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha de Inscripción</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 16px; }
        .header { margin-bottom: 12px; }
        .footer { margin-top: 16px; font-size: 12px; color: #666; }
        .notice { margin-top: 8px; font-weight: bold; color: #a94442; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Ficha de Inscripción</h2>
        </div>
        <p>Hola,</p>
        <p>¡Bienvenido/a! Nos alegra acompañarte en este proceso de inscripción. 
           Te damos la bienvenida a nuestra comunidad educativa y estamos a tu disposición para apoyarte en cada paso.</p>
        <p>Se adjunta la ficha de inscripción del alumno:
            <strong>{{ $alumno->primer_nombre ?? $alumno->nombres }} {{ $alumno->primer_apellido ?? $alumno->apellidos }}</strong>.</p>
        <p>Si tienes alguna duda, por favor contáctanos a través de nuestros canales oficiales.
           No respondas a este correo.</p>

        <div class="footer">
            <p>Este correo fue enviado automáticamente por el sistema.</p>
            <p class="notice">Por favor no respondas a este mensaje; este buzón no recibe respuestas y
               solo se utiliza para notificaciones.</p>
        </div>
    </div>
</body>
</html>