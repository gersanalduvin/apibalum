# Redis para Caché en Laravel

## Configuración Actual

Tu proyecto ya está configurado para usar Redis como sistema de caché:

### Variables de Entorno (.env)
```env
# Configuración de Caché
CACHE_STORE=redis

# Configuración de Redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
```

### Conexiones Redis Configuradas

1. **default** - Base de datos 0 (para colas y uso general)
2. **cache** - Base de datos 1 (específica para caché)

## Uso Básico del Caché

### 1. Almacenar Datos en Caché

```php
use Illuminate\Support\Facades\Cache;

// Almacenar por tiempo indefinido
Cache::put('key', 'value');

// Almacenar por tiempo específico (en segundos)
Cache::put('user_1', $user, 3600); // 1 hora

// Almacenar por tiempo específico (usando Carbon)
Cache::put('settings', $settings, now()->addMinutes(30));

// Almacenar solo si no existe
Cache::add('unique_key', 'value', 3600);

// Almacenar para siempre
Cache::forever('permanent_data', $data);
```

### 2. Recuperar Datos del Caché

```php
// Obtener valor
$value = Cache::get('key');

// Obtener con valor por defecto
$value = Cache::get('key', 'default_value');

// Obtener con closure como valor por defecto
$value = Cache::get('expensive_data', function () {
    return DB::table('users')->get();
});

// Verificar si existe
if (Cache::has('key')) {
    $value = Cache::get('key');
}
```

### 3. Recuperar y Almacenar (Remember)

```php
// Si existe lo devuelve, si no ejecuta el closure y lo almacena
$users = Cache::remember('users', 3600, function () {
    return DB::table('users')->get();
});

// Versión "forever"
$settings = Cache::rememberForever('app_settings', function () {
    return DB::table('settings')->pluck('value', 'key');
});
```

### 4. Eliminar del Caché

```php
// Eliminar una clave específica
Cache::forget('key');

// Limpiar todo el caché
Cache::flush();

// Obtener y eliminar
$value = Cache::pull('key');
```

## Ejemplos Prácticos

### 1. Caché de Consultas de Base de Datos

```php
class UserController extends Controller
{
    public function index()
    {
        $users = Cache::remember('all_users', 600, function () {
            return User::with('profile')->get();
        });
        
        return response()->json($users);
    }
    
    public function show($id)
    {
        $user = Cache::remember("user_{$id}", 3600, function () use ($id) {
            return User::with('profile', 'posts')->findOrFail($id);
        });
        
        return response()->json($user);
    }
    
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->update($request->validated());
        
        // Invalidar caché relacionado
        Cache::forget("user_{$id}");
        Cache::forget('all_users');
        
        return response()->json($user);
    }
}
```

### 2. Caché de Configuraciones

```php
class ConfigService
{
    public function getAppSettings()
    {
        return Cache::rememberForever('app_settings', function () {
            return [
                'maintenance_mode' => false,
                'max_upload_size' => '10MB',
                'allowed_extensions' => ['jpg', 'png', 'pdf'],
                'api_rate_limit' => 1000
            ];
        });
    }
    
    public function updateSetting($key, $value)
    {
        // Actualizar en base de datos
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        
        // Invalidar caché
        Cache::forget('app_settings');
    }
}
```

### 3. Caché de Resultados de API Externa

```php
class WeatherService
{
    public function getCurrentWeather($city)
    {
        $cacheKey = "weather_{$city}";
        
        return Cache::remember($cacheKey, 1800, function () use ($city) {
            // Llamada a API externa (cachear por 30 minutos)
            $response = Http::get("https://api.weather.com/current/{$city}");
            return $response->json();
        });
    }
}
```

### 4. Caché con Tags (Agrupación)

```php
// Almacenar con tags
Cache::tags(['users', 'posts'])->put('user_posts_1', $data, 3600);
Cache::tags(['users'])->put('user_profile_1', $profile, 3600);

// Invalidar por tags
Cache::tags(['users'])->flush(); // Elimina todo lo etiquetado con 'users'
```

## Comandos Artisan para Caché

```bash
# Limpiar todo el caché
php artisan cache:clear

# Limpiar caché de configuración
php artisan config:clear

# Limpiar caché de rutas
php artisan route:clear

# Limpiar caché de vistas
php artisan view:clear

# Crear caché de configuración (producción)
php artisan config:cache

# Crear caché de rutas (producción)
php artisan route:cache
```

## Mejores Prácticas

### 1. Nomenclatura de Claves

```php
// Usar prefijos descriptivos
$userKey = "user_profile_{$userId}";
$settingsKey = "app_settings_global";
$apiKey = "external_api_weather_{$city}";
```

### 2. Tiempos de Expiración Apropiados

```php
// Datos que cambian frecuentemente - 5-15 minutos
Cache::put('live_stats', $stats, 300);

// Datos de usuario - 30-60 minutos
Cache::put('user_preferences', $prefs, 3600);

// Configuraciones - 24 horas o forever
Cache::put('app_config', $config, 86400);

// Datos de API externa - según la frecuencia de actualización
Cache::put('exchange_rates', $rates, 1800); // 30 minutos
```

### 3. Invalidación Inteligente

```php
class PostService
{
    public function createPost($data)
    {
        $post = Post::create($data);
        
        // Invalidar cachés relacionados
        Cache::forget('recent_posts');
        Cache::forget("user_posts_{$post->user_id}");
        Cache::forget('posts_count');
        
        return $post;
    }
}
```

### 4. Manejo de Errores

```php
try {
    $data = Cache::remember('expensive_operation', 3600, function () {
        // Operación costosa que puede fallar
        return $this->performExpensiveOperation();
    });
} catch (Exception $e) {
    // Si falla, usar datos por defecto y no cachear
    Log::error('Cache operation failed: ' . $e->getMessage());
    return $this->getDefaultData();
}
```

## Monitoreo y Debugging

### 1. Verificar Conexión Redis

```php
// En un controlador o comando
public function testRedisConnection()
{
    try {
        Cache::put('test_key', 'test_value', 60);
        $value = Cache::get('test_key');
        
        if ($value === 'test_value') {
            return 'Redis cache is working!';
        }
    } catch (Exception $e) {
        return 'Redis cache error: ' . $e->getMessage();
    }
}
```

### 2. Estadísticas de Caché

```php
class CacheStatsController extends Controller
{
    public function getStats()
    {
        $redis = Redis::connection('cache');
        
        return [
            'info' => $redis->info(),
            'memory_usage' => $redis->info('memory'),
            'keyspace' => $redis->info('keyspace'),
            'total_keys' => $redis->dbsize()
        ];
    }
}
```

## Configuración de Producción

### Variables de Entorno para Producción

```env
# Usar conexión segura si es necesario
REDIS_PASSWORD=your_secure_password

# Configurar prefijos únicos
CACHE_PREFIX=myapp_cache_
REDIS_PREFIX=myapp_redis_

# Configurar timeouts apropiados
REDIS_MAX_RETRIES=3
REDIS_BACKOFF_ALGORITHM=decorrelated_jitter
```

### Optimizaciones

```php
// En config/cache.php para producción
'redis' => [
    'driver' => 'redis',
    'connection' => 'cache',
    'lock_connection' => 'default',
    'serializer' => 'igbinary', // Más eficiente que PHP serialize
    'compression' => 'lz4',     // Compresión para ahorrar memoria
],
```

## Troubleshooting

### Problemas Comunes

1. **Redis no está instalado**
   ```bash
   # Windows (con Chocolatey)
   choco install redis-64
   
   # O descargar desde: https://github.com/microsoftarchive/redis/releases
   ```

2. **Extensión phpredis no instalada**
   - Verificar que `extension=redis` esté en php.ini
   - Reiniciar el servidor web

3. **Problemas de conexión**
   ```bash
   # Verificar que Redis esté corriendo
   redis-cli ping
   # Debería responder: PONG
   ```

4. **Memoria insuficiente**
   ```bash
   # Configurar límite de memoria en redis.conf
   maxmemory 256mb
   maxmemory-policy allkeys-lru
   ```

## API Endpoints Disponibles

Tu aplicación incluye un `CacheController` con endpoints para probar y gestionar el caché:

### Endpoints de Prueba y Gestión

```http
# Probar conexión Redis
GET /api/cache/test

# Obtener usuarios cacheados (ejemplo práctico)
GET /api/cache/users

# Obtener estadísticas de Redis
GET /api/cache/stats
```

### Endpoints de Almacenamiento y Recuperación

```http
# Almacenar datos en caché
POST /api/cache/store
Content-Type: application/json
{
    "key": "mi_clave",
    "value": "mi_valor",
    "ttl": 3600
}

# Recuperar datos del caché
GET /api/cache/get/{key}

# Eliminar una clave específica
DELETE /api/cache/forget/{key}

# Limpiar todo el caché
DELETE /api/cache/flush
```

### Endpoints con Tags (Agrupación)

```http
# Almacenar con tags
POST /api/cache/tags/store
Content-Type: application/json
{
    "key": "user_data_1",
    "value": {"name": "Juan", "email": "juan@example.com"},
    "tags": ["users", "profiles"],
    "ttl": 3600
}

# Limpiar caché por tags
DELETE /api/cache/tags/flush
Content-Type: application/json
{
    "tags": ["users"]
}
```

### Ejemplos de Uso con cURL

```bash
# Probar conexión
curl -X GET http://localhost:8000/api/cache/test

# Almacenar datos
curl -X POST http://localhost:8000/api/cache/store \
  -H "Content-Type: application/json" \
  -d '{"key":"test_key","value":"Hello Redis!","ttl":300}'

# Recuperar datos
curl -X GET http://localhost:8000/api/cache/get/test_key

# Ver estadísticas
curl -X GET http://localhost:8000/api/cache/stats
```

## Próximos Pasos

1. **Instalar Redis** si no está instalado
2. **Iniciar el servicio Redis**
3. **Probar la conexión** usando `/api/cache/test`
4. **Experimentar con los endpoints** para familiarizarte con el caché
5. **Implementar caché** en tus controladores y servicios
6. **Monitorear el rendimiento** usando `/api/cache/stats`

¡Redis está listo para usar como sistema de caché en tu aplicación Laravel!