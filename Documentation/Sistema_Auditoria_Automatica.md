# Sistema de Auditoría Automática

## 📋 Descripción General

El sistema de auditoría automática permite el seguimiento completo de cambios en todos los modelos del proyecto, registrando automáticamente quién, cuándo y qué cambios se realizaron en cada registro.

## 🎯 Características Principales

- **Auditoría Automática**: Todos los modelos nuevos incluyen automáticamente el trait `Auditable`
- **Trazabilidad Completa**: Registro de creación, actualización y eliminación
- **Campos de Auditoría**: `created_by`, `updated_by`, `deleted_by`, `cambios`
- **Soft Deletes**: Eliminación lógica para preservar historial
- **Comandos Artisan**: Herramientas para generar modelos y consultar auditorías

## 🏗️ Estructura de Auditoría

### Campos Obligatorios en Modelos

Todos los modelos deben incluir los siguientes campos:

```php
protected $fillable = [
    // Campos específicos del modelo
    'created_by',
    'updated_by', 
    'deleted_by',
    'cambios'
];

protected $casts = [
    'cambios' => 'array',
    'deleted_at' => 'datetime'
];
```

### Migración con Campos de Auditoría

```php
Schema::create('nombre_tabla', function (Blueprint $table) {
    $table->id();
    // Campos específicos del modelo
    
    // Campos obligatorios de auditoría
    $table->unsignedBigInteger('created_by')->nullable();
    $table->unsignedBigInteger('updated_by')->nullable();
    $table->unsignedBigInteger('deleted_by')->nullable();
    $table->json('cambios')->nullable();
    $table->softDeletes();
    $table->timestamps();
    
    // Índices para auditoría
    $table->index(['created_by', 'updated_by', 'deleted_by']);
});
```

## 🛠️ Comandos Artisan Disponibles

### 1. Generar Modelo con Auditoría

```bash
# Crear modelo con trait Auditable incluido
php artisan make:auditable-model NombreModelo

# Crear modelo con migración
php artisan make:auditable-model NombreModelo --migration
```

**Características del comando:**
- Incluye automáticamente el trait `Auditable`
- Configura `SoftDeletes`
- Añade campos de auditoría a `$fillable`
- Configura `$casts` apropiados
- Proporciona instrucciones para la migración

### 2. Consultar Auditorías

```bash
# Consultar auditorías de un modelo
php artisan audit:query NombreModelo --limit=10

# Filtrar por evento específico
php artisan audit:query User --event=updated --limit=5

# Filtrar por usuario
php artisan audit:query Producto --user=123 --limit=10
```

### 3. Verificar Sistema de Auditoría

```bash
# Verificar configuración de auditoría
php artisan audit:test --cleanup
```

## 📊 Modelo de Datos de Auditoría

### Tabla `audits`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único de la auditoría |
| `uuid` | string | UUID único para identificación |
| `user_id` | bigint | ID del usuario que realizó la acción |
| `model_type` | string | Clase del modelo auditado |
| `model_id` | bigint | ID del registro auditado |
| `event` | string | Tipo de evento (created, updated, deleted) |
| `table_name` | string | Nombre de la tabla |
| `column_name` | string | Columna modificada (para cambios específicos) |
| `old_value` | text | Valor anterior |
| `new_value` | text | Valor nuevo |
| `old_values` | json | Todos los valores anteriores |
| `new_values` | json | Todos los valores nuevos |
| `ip` | string | Dirección IP del usuario |
| `user_agent` | text | User Agent del navegador |
| `metadata` | json | Información adicional |
| `created_at` | timestamp | Fecha de creación de la auditoría |

## 🔧 Uso del Trait Auditable

### Configuración Automática

El trait `Auditable` se configura automáticamente con:

```php
use App\Traits\Auditable;

class MiModelo extends Model
{
    use SoftDeletes, Auditable;
    
    // Configuración automática:
    // - Eventos auditables: created, updated, deleted
    // - Campos excluidos: updated_at, created_at, deleted_at, cambios
    // - Usuario actual obtenido del contexto de autenticación
}
```

### Métodos Disponibles

```php
$modelo = MiModelo::find(1);

// Obtener todas las auditorías
$auditorias = $modelo->audits;

// Obtener auditorías por evento
$creaciones = $modelo->audits()->where('event', 'created')->get();
$actualizaciones = $modelo->audits()->where('event', 'updated')->get();

// Obtener cambios recientes
$cambiosRecientes = $modelo->getRecentChanges(10);

// Obtener cambios por usuario
$cambiosPorUsuario = $modelo->getChangesByUser($userId);
```

## 📝 Reglas de Implementación

### ✅ Obligatorio

1. **NUNCA** crear un modelo sin el trait `Auditable`
2. **SIEMPRE** incluir los campos de auditoría en las migraciones
3. **OBLIGATORIO** usar el comando `make:auditable-model` para generar modelos
4. **VERIFICAR** que la auditoría funcione con `php artisan audit:test`

### 🚫 Modelos Excluidos

Los siguientes modelos NO requieren auditoría:
- `Audit` (modelo de auditoría)
- `Role` (roles del sistema)
- `ConfigPlanPago` (configuración específica)

### 📋 Checklist de Implementación

- [ ] Modelo creado con `make:auditable-model`
- [ ] Migración incluye campos de auditoría
- [ ] Trait `Auditable` aplicado
- [ ] Campos de auditoría en `$fillable`
- [ ] `$casts` configurado correctamente
- [ ] Prueba con `audit:test` exitosa
- [ ] Documentación actualizada

## 🔍 Consultas de Auditoría

### Ejemplos de Consultas

```php
// Obtener todas las auditorías de un modelo
$auditorias = Audit::where('model_type', 'App\Models\User')
    ->orderBy('created_at', 'desc')
    ->get();

// Filtrar por evento
$creaciones = Audit::where('model_type', 'App\Models\Producto')
    ->where('event', 'created')
    ->get();

// Filtrar por usuario
$cambiosUsuario = Audit::where('user_id', 123)
    ->where('model_type', 'App\Models\Categoria')
    ->get();

// Obtener cambios en un rango de fechas
$cambiosRecientes = Audit::where('model_type', 'App\Models\Empresa')
    ->whereBetween('created_at', [$fechaInicio, $fechaFin])
    ->get();
```

## 🛡️ Seguridad y Privacidad

- **Datos Sensibles**: Los campos de contraseña se excluyen automáticamente
- **IP y User Agent**: Se registran para trazabilidad de seguridad
- **Soft Deletes**: Los registros eliminados se mantienen para auditoría
- **Acceso Controlado**: Solo usuarios autenticados pueden generar auditorías

## 📈 Rendimiento

- **Índices**: Se crean automáticamente en campos de auditoría
- **Limpieza**: Comando `audit:test --cleanup` para mantenimiento
- **Consultas Optimizadas**: Uso de relaciones y filtros eficientes

## 🔄 Mantenimiento

### Limpieza de Auditorías Antiguas

```bash
# Limpiar auditorías de más de 1 año
php artisan audit:cleanup --days=365

# Limpiar auditorías de un modelo específico
php artisan audit:cleanup --model=User --days=180
```

### Verificación de Integridad

```bash
# Verificar integridad del sistema
php artisan audit:verify

# Reparar inconsistencias
php artisan audit:repair
```

## 📚 Recursos Adicionales

- **Trait Auditable**: `app/Traits/Auditable.php`
- **Modelo Audit**: `app/Models/Audit.php`
- **Comandos**: `app/Console/Commands/`
- **Reglas del Proyecto**: `.trae/rules/project_rules.md`

---

**Fecha de Creación**: {{ date('Y-m-d') }}  
**Versión**: 1.0  
**Autor**: Sistema de Auditoría Automática