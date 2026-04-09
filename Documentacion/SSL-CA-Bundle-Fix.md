# Solución: SSL CA Bundle Not Found - AWS SQS

## Problema
Error en producción al enviar correos electrónicos a través de SQS:
```
Error executing "SendMessage" on "https://sqs.us-east-2.amazonaws.com/"; 
AWS HTTP error: SSL CA bundle not found
```

## Causa
El SDK de AWS para PHP no puede verificar los certificados SSL de AWS porque no encuentra el archivo de certificados CA (Certificate Authority) bundle.

## Solución Implementada

### 1. Configuración del CA Bundle en .env
```env
AWS_CA_BUNDLE=C:\laragon6\etc\ssl\cacert.pem
```

### 2. Configuración Global de AWS (config/aws.php)
```php
'http' => [
    'verify' => env('AWS_CA_BUNDLE', 'C:\\laragon6\\etc\\ssl\\cacert.pem'),
    'timeout' => 60,
    'connect_timeout' => 60,
],
```

### 3. Configuración Específica de SQS (config/queue.php)
```php
'sqs' => [
    'driver' => 'sqs',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
    'queue' => env('SQS_QUEUE', 'default'),
    'suffix' => env('SQS_SUFFIX'),
    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    'after_commit' => false,
    'http' => [
        'verify' => env('AWS_CA_BUNDLE', 'C:\\laragon6\\etc\\ssl\\cacert.pem'),
        'timeout' => 60,
        'connect_timeout' => 60,
    ],
],
```

### 4. Configuración Personalizada en AppServiceProvider
El `AppServiceProvider.php` ya incluye una configuración personalizada para SQS que inyecta las opciones HTTP:

```php
Queue::extend('sqs', function () {
    return new class extends SqsConnector {
        public function connect(array $config)
        {
            $ca = env('AWS_CA_BUNDLE', 'C:\\laragon6\\etc\\ssl\\cacert.pem');

            $http = $config['http'] ?? [];
            $config['http'] = array_merge([
                'timeout' => 60,
                'connect_timeout' => 60,
            ], $http, [
                'verify' => $ca,
                'curl' => [
                    CURLOPT_CAINFO => $ca,
                ],
            ]);

            return parent::connect($config);
        }
    };
});
```

## Verificación de la Solución

### 1. Verificar que el archivo CA existe:
```bash
Test-Path "C:\laragon6\etc\ssl\cacert.pem"
```

### 2. Limpiar caché de configuración:
```bash
php artisan config:clear
```

### 3. Probar la conexión SQS:
```bash
php artisan queue:work --once --timeout=30
```

## Archivos Modificados
- `config/aws.php` - Configuración global del CA bundle
- `config/queue.php` - Configuración específica de SQS
- `.env` - Variable de entorno AWS_CA_BUNDLE

## Notas Importantes
- El archivo `cacert.pem` debe existir en la ruta especificada
- En producción, asegúrate de que la ruta del CA bundle sea correcta para el servidor
- La configuración funciona tanto para desarrollo local como para producción
- Los timeouts están configurados a 60 segundos para evitar timeouts prematuros

## Comandos de Verificación Post-Implementación
```bash
# Limpiar caché
php artisan config:clear

# Verificar configuración de colas
php artisan queue:work --once

# Probar envío de correo (si hay jobs pendientes)
php artisan queue:restart
```