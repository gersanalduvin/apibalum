# Configuración de Redis y Sistema de Colas en Laravel

## Descripción General

Este documento describe la configuración completa de Redis como sistema de colas para manejar tareas asíncronas como envío de correos electrónicos y procesamiento de datos en Laravel.

## Configuración Inicial

### 1. Variables de Entorno (.env)

```env
# Configuración de Colas
QUEUE_CONNECTION=redis
CACHE_STORE=redis

# Configuración de Redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_QUEUE_CONNECTION=default

# Configuración de Colas
REDIS_QUEUE=default
REDIS_QUEUE_RETRY_AFTER=90

# Colas Específicas
QUEUE_EMAIL=emails
QUEUE_NOTIFICATIONS=notifications
QUEUE_PROCESSING=processing
QUEUE_HIGH_PRIORITY=high
QUEUE_LOW_PRIORITY=low
```

### 2. Instalación de Redis

**Windows (usando Chocolatey):**
```bash
choco install redis-64
```

**Windows (manual):**
1. Descargar Redis desde: https://github.com/microsoftarchive/redis/releases
2. Extraer y ejecutar `redis-server.exe`

**Verificar instalación:**
```bash
redis-cli ping
# Debería responder: PONG
```

## Jobs Disponibles

### 1. SendEmailJob

**Propósito:** Maneja el envío asíncrono de correos electrónicos usando AWS SES.

**Características:**
- 3 intentos máximos
- Timeout de 60 segundos
- Cola específica: `emails`
- Soporte para emails simples y con plantillas
- Logging completo de errores y éxitos

**Uso programático:**
```php
use App\Jobs\SendEmailJob;

// Email simple
$emailData = [
    'to' => 'usuario@ejemplo.com',
    'subject' => 'Asunto del correo',
    'body' => 'Contenido del correo',
    'from' => 'remitente@ejemplo.com' // opcional
];

$job = new SendEmailJob($emailData, 'simple');
dispatch($job);

// Email con plantilla
$emailData = [
    'to' => 'usuario@ejemplo.com',
    'template' => 'welcome-template',
    'templateData' => ['name' => 'Juan', 'company' => 'Mi Empresa'],
    'from' => 'remitente@ejemplo.com' // opcional
];

$job = new SendEmailJob($emailData, 'templated');
dispatch($job);
```

### 2. ProcessDataJob

**Propósito:** Maneja tareas de procesamiento de datos con diferentes prioridades.

**Características:**
- 5 intentos máximos
- Timeout de 300 segundos (5 minutos)
- Colas según prioridad: `high`, `processing`, `low`
- Múltiples tipos de tareas soportadas

**Tipos de tareas disponibles:**
- `image_processing`: Procesamiento de imágenes
- `data_export`: Exportación de datos
- `file_cleanup`: Limpieza de archivos temporales
- `report_generation`: Generación de reportes
- `notification_batch`: Envío masivo de notificaciones

**Uso programático:**
```php
use App\Jobs\ProcessDataJob;

// Procesamiento de imagen con alta prioridad
$taskData = [
    'image_path' => '/path/to/image.jpg'
];

$job = new ProcessDataJob('image_processing', $taskData, 'high');
dispatch($job);

// Exportación de datos con prioridad normal
$taskData = [
    'format' => 'csv',
    'data' => $arrayDeDatos
];

$job = new ProcessDataJob('data_export', $taskData, 'normal');
dispatch($job);
```

## API Endpoints

### 1. Despachar Job de Email

**Endpoint:** `POST /api/queue/email`

**Parámetros:**
```json
{
    "to": "usuario@ejemplo.com",
    "type": "simple", // "simple" o "templated"
    "subject": "Asunto del correo", // requerido para type=simple
    "body": "Contenido del correo", // requerido para type=simple
    "template": "welcome-template", // requerido para type=templated
    "templateData": { // opcional para type=templated
        "name": "Juan",
        "company": "Mi Empresa"
    },
    "from": "remitente@ejemplo.com", // opcional
    "delay": 60 // opcional, delay en segundos
}
```

**Respuesta exitosa:**
```json
{
    "success": true,
    "message": "Job de email despachado exitosamente",
    "job_id": "unique-job-id",
    "queue": "emails"
}
```

### 2. Despachar Job de Procesamiento

**Endpoint:** `POST /api/queue/processing`

**Parámetros:**
```json
{
    "task_type": "image_processing", // Ver tipos disponibles arriba
    "task_data": {
        "image_path": "/path/to/image.jpg"
    },
    "priority": "high", // "high", "normal", "low"
    "delay": 0 // opcional, delay en segundos
}
```

**Respuesta exitosa:**
```json
{
    "success": true,
    "message": "Job de procesamiento despachado exitosamente",
    "job_id": "unique-job-id",
    "task_type": "image_processing",
    "priority": "high",
    "queue": "high"
}
```

### 3. Estadísticas de Colas

**Endpoint:** `GET /api/queue/stats`

**Respuesta:**
```json
{
    "success": true,
    "queues": {
        "emails": {
            "queue_name": "emails",
            "size": 5
        },
        "processing": {
            "queue_name": "processing",
            "size": 2
        },
        "high_priority": {
            "queue_name": "high",
            "size": 0
        },
        "low_priority": {
            "queue_name": "low",
            "size": 1
        },
        "notifications": {
            "queue_name": "notifications",
            "size": 0
        }
    },
    "connection": "redis",
    "redis_host": "127.0.0.1",
    "redis_port": "6379"
}
```

### 4. Información del Sistema

**Endpoint:** `GET /api/queue/system-info`

**Respuesta:**
```json
{
    "success": true,
    "system_info": {
        "queue_connection": "redis",
        "redis_client": "phpredis",
        "redis_host": "127.0.0.1",
        "redis_port": "6379",
        "redis_database": "0",
        "cache_store": "redis",
        "available_queues": {
            "emails": "emails",
            "processing": "processing",
            "high_priority": "high",
            "low_priority": "low",
            "notifications": "notifications"
        }
    }
}
```

### 5. Limpiar Cola

**Endpoint:** `POST /api/queue/clear`

**Parámetros:**
```json
{
    "queue_name": "emails"
}
```

**Respuesta:**
```json
{
    "success": true,
    "message": "Cola 'emails' limpiada exitosamente",
    "jobs_cleared": 5
}
```

## Comandos de Artisan

### Ejecutar Workers

**Worker para todas las colas:**
```bash
php artisan queue:work redis
```

**Worker para cola específica:**
```bash
php artisan queue:work redis --queue=emails
```

**Worker con múltiples colas (por prioridad):**
```bash
php artisan queue:work redis --queue=high,emails,processing,low
```

**Worker con configuraciones específicas:**
```bash
php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
```

### Monitoreo y Gestión

**Ver jobs fallidos:**
```bash
php artisan queue:failed
```

**Reintentar job fallido:**
```bash
php artisan queue:retry {id}
```

**Reintentar todos los jobs fallidos:**
```bash
php artisan queue:retry all
```

**Limpiar jobs fallidos:**
```bash
php artisan queue:flush
```

**Pausar/reanudar colas:**
```bash
php artisan queue:pause-all
php artisan queue:continue-all
```

## Configuración de Producción

### 1. Supervisor (Linux)

**Archivo de configuración:** `/etc/supervisor/conf.d/laravel-worker.conf`

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/app/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=8
redirect_stderr=true
stdout_logfile=/path/to/your/app/storage/logs/worker.log
stopwaitsecs=3600
```

### 2. Configuración de Redis para Producción

**redis.conf:**
```
# Configuración de memoria
maxmemory 2gb
maxmemory-policy allkeys-lru

# Persistencia
save 900 1
save 300 10
save 60 10000

# Configuración de red
bind 127.0.0.1
port 6379
timeout 0
tcp-keepalive 300

# Configuración de logs
loglevel notice
logfile /var/log/redis/redis-server.log
```

### 3. Monitoreo

**Horizon (recomendado para Laravel):**
```bash
composer require laravel/horizon
php artisan horizon:install
php artisan horizon
```

## Mejores Prácticas

### 1. Gestión de Errores
- Implementar logging detallado en todos los jobs
- Usar el método `failed()` para manejar jobs que fallan definitivamente
- Configurar alertas para jobs fallidos críticos

### 2. Optimización de Performance
- Usar múltiples workers para colas de alta prioridad
- Configurar timeouts apropiados según el tipo de tarea
- Monitorear el uso de memoria de Redis

### 3. Seguridad
- Configurar autenticación en Redis para producción
- Usar conexiones SSL/TLS cuando sea posible
- Limitar acceso de red a Redis

### 4. Escalabilidad
- Usar Redis Cluster para aplicaciones de gran escala
- Implementar balanceadores de carga para workers
- Configurar réplicas de Redis para alta disponibilidad

## Troubleshooting

### Problemas Comunes

**1. Jobs no se procesan:**
- Verificar que Redis esté ejecutándose
- Confirmar que los workers estén activos
- Revisar logs de Laravel y Redis

**2. Jobs fallan constantemente:**
- Verificar configuración de AWS (para emails)
- Revisar permisos de archivos
- Confirmar que las dependencias estén instaladas

**3. Redis se queda sin memoria:**
- Configurar `maxmemory` y `maxmemory-policy`
- Limpiar jobs antiguos regularmente
- Monitorear el crecimiento de las colas

### Comandos de Diagnóstico

```bash
# Verificar conexión a Redis
redis-cli ping

# Ver información de Redis
redis-cli info

# Listar todas las claves
redis-cli keys "*"

# Ver tamaño de una cola específica
redis-cli llen "queues:emails"

# Monitorear comandos en tiempo real
redis-cli monitor
```

## Conclusión

Este sistema de colas con Redis proporciona una solución robusta y escalable para manejar tareas asíncronas en Laravel. La configuración incluye manejo de errores, logging detallado, y múltiples tipos de colas para diferentes prioridades y tipos de tareas.

Para soporte adicional, consultar la documentación oficial de Laravel Queues y Redis.