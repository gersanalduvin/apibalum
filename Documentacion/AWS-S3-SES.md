# Configuración y Uso de AWS S3 y SES

## Configuración Inicial

### 1. Variables de Entorno (.env)

Asegúrate de configurar las siguientes variables en tu archivo `.env`:

```env
# AWS Configuration
AWS_ACCESS_KEY_ID=your-access-key-id
AWS_SECRET_ACCESS_KEY=your-secret-access-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-s3-bucket-name
AWS_USE_PATH_STYLE_ENDPOINT=false

# AWS S3 Configuration
AWS_S3_REGION=us-east-1
AWS_S3_BUCKET=your-s3-bucket-name
AWS_S3_URL=

# AWS SES Configuration
AWS_SES_REGION=us-east-1
AWS_SES_KEY=your-access-key-id
AWS_SES_SECRET=your-secret-access-key

# Filesystem and Mail Configuration
FILESYSTEM_DISK=s3
MAIL_MAILER=ses
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="Your App Name"
```

### 2. Credenciales AWS

Para obtener las credenciales AWS:

1. Ve a la consola de AWS IAM
2. Crea un nuevo usuario con acceso programático
3. Asigna las políticas necesarias:
   - `AmazonS3FullAccess` (o una política más restrictiva)
   - `AmazonSESFullAccess` (o una política más restrictiva)
4. Guarda el Access Key ID y Secret Access Key

## Uso de S3 (Almacenamiento de Archivos)

### Endpoints Disponibles

Todos los endpoints requieren autenticación con Sanctum (`Authorization: Bearer {token}`).

#### 1. Subir Archivo

```http
POST /api/aws/s3/upload
Content-Type: multipart/form-data

file: [archivo]
directory: "uploads" (opcional)
```

**Ejemplo con cURL:**
```bash
curl -X POST \
  http://localhost:8000/api/aws/s3/upload \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -F 'file=@/path/to/your/file.jpg' \
  -F 'directory=images'
```

**Respuesta:**
```json
{
  "success": true,
  "url": "https://your-bucket.s3.amazonaws.com/images/filename_2024-01-15_14-30-45_abc12345.jpg",
  "key": "images/filename_2024-01-15_14-30-45_abc12345.jpg",
  "bucket": "your-bucket-name"
}
```

#### 2. Eliminar Archivo

```http
DELETE /api/aws/s3/delete
Content-Type: application/json

{
  "key": "images/filename_2024-01-15_14-30-45_abc12345.jpg"
}
```

#### 3. Obtener URL Firmada (Presigned URL)

```http
POST /api/aws/s3/presigned-url
Content-Type: application/json

{
  "key": "images/filename_2024-01-15_14-30-45_abc12345.jpg",
  "expires_in_minutes": 60
}
```

### Uso Directo del Servicio S3

```php
use App\Services\S3Service;

// En un controlador o servicio
public function uploadExample(Request $request, S3Service $s3Service)
{
    $file = $request->file('image');
    $result = $s3Service->uploadFile($file, 'profile-images');
    
    if ($result['success']) {
        // Guardar la URL en la base de datos
        $user = auth()->user();
        $user->profile_image = $result['url'];
        $user->save();
    }
    
    return response()->json($result);
}
```

## Uso de SES (Envío de Emails)

### Endpoints Disponibles

#### 1. Enviar Email Simple

```http
POST /api/aws/ses/send-email
Content-Type: application/json

{
  "to": "recipient@example.com",
  "subject": "Asunto del email",
  "text": "Contenido en texto plano",
  "html": "<h1>Contenido HTML</h1>",
  "from": "sender@yourdomain.com" (opcional),
  "cc": ["cc@example.com"] (opcional),
  "bcc": ["bcc@example.com"] (opcional)
}
```

#### 2. Enviar Email con Plantilla

```http
POST /api/aws/ses/send-templated-email
Content-Type: application/json

{
  "to": "recipient@example.com",
  "template": "WelcomeTemplate",
  "template_data": {
    "name": "Juan Pérez",
    "company": "Mi Empresa"
  }
}
```

#### 3. Verificar Dirección de Email

```http
POST /api/aws/ses/verify-email
Content-Type: application/json

{
  "email": "newemail@yourdomain.com"
}
```

#### 4. Obtener Estadísticas de Envío

```http
GET /api/aws/ses/statistics
```

#### 5. Obtener Emails Verificados

```http
GET /api/aws/ses/verified-emails
```

### Uso Directo del Servicio SES

```php
use App\Services\SesService;

// Enviar email de bienvenida
public function sendWelcomeEmail(User $user, SesService $sesService)
{
    $emailData = [
        'to' => $user->email,
        'subject' => 'Bienvenido a nuestra plataforma',
        'html' => view('emails.welcome', ['user' => $user])->render(),
        'text' => 'Bienvenido ' . $user->name . ' a nuestra plataforma.'
    ];
    
    $result = $sesService->sendEmail($emailData);
    
    if ($result['success']) {
        Log::info('Welcome email sent', ['user_id' => $user->id, 'message_id' => $result['message_id']]);
    } else {
        Log::error('Failed to send welcome email', ['user_id' => $user->id, 'error' => $result['error']]);
    }
    
    return $result;
}
```

## Configuración Adicional

### Políticas IAM Recomendadas

#### Para S3:
```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "s3:GetObject",
                "s3:PutObject",
                "s3:DeleteObject",
                "s3:ListBucket"
            ],
            "Resource": [
                "arn:aws:s3:::your-bucket-name",
                "arn:aws:s3:::your-bucket-name/*"
            ]
        }
    ]
}
```

#### Para SES:
```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "ses:SendEmail",
                "ses:SendTemplatedEmail",
                "ses:SendRawEmail",
                "ses:VerifyEmailIdentity",
                "ses:GetSendStatistics",
                "ses:ListVerifiedEmailAddresses"
            ],
            "Resource": "*"
        }
    ]
}
```

### Configuración de CORS para S3

Si necesitas acceso desde el frontend:

```json
[
    {
        "AllowedHeaders": ["*"],
        "AllowedMethods": ["GET", "PUT", "POST", "DELETE"],
        "AllowedOrigins": ["http://localhost:3000", "https://yourdomain.com"],
        "ExposeHeaders": ["ETag"]
    }
]
```

## Consideraciones de Seguridad

1. **Nunca expongas las credenciales AWS** en el código fuente
2. **Usa políticas IAM restrictivas** que solo otorguen los permisos necesarios
3. **Implementa validación de archivos** antes de subirlos a S3
4. **Verifica las direcciones de email** antes de enviar emails masivos
5. **Monitorea el uso** para detectar actividad sospechosa
6. **Usa HTTPS** siempre para las comunicaciones

## Troubleshooting

### Errores Comunes

1. **"The AWS Access Key Id you provided does not exist"**
   - Verifica que las credenciales sean correctas
   - Asegúrate de que el usuario IAM existe

2. **"Access Denied"**
   - Revisa las políticas IAM del usuario
   - Verifica que el bucket S3 existe y tienes permisos

3. **"Email address not verified"**
   - Verifica la dirección de email en la consola de SES
   - En modo sandbox, solo puedes enviar a emails verificados

4. **"InvalidParameterValue"**
   - Revisa que todos los parámetros requeridos estén presentes
   - Verifica el formato de los datos enviados