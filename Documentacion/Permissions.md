# Sistema de Permisos Categorizados - Laravel API

## Descripción General

El sistema de permisos está diseñado para manejar de forma centralizada todos los permisos de la aplicación. Utiliza una **estructura jerárquica de categorías** donde cada categoría contiene módulos, y cada módulo tiene acciones específicas (ver, crear, editar, eliminar, etc.).

### Estructura Jerárquica
```
Categoría
├── Módulo 1
│   ├── Acción 1 (ej: ver)
│   ├── Acción 2 (ej: crear)
│   └── Acción N
├── Módulo 2
└── Módulo N
```

## Estructura de Archivos

```
app/
├── Services/
│   └── PermissionService.php          # Servicio principal de permisos
├── Http/
│   └── Controllers/
│       └── Api/
│           └── V1/
│               └── PermissionController.php  # Controlador API
routes/
└── api/
    └── v1/
        └── permissions.php             # Rutas de permisos
```

## Categorías y Permisos Disponibles

### 📂 Categoría: Configuración
Permisos relacionados con la configuración del sistema y gestión de usuarios.

#### **Roles**
- `roles.ver` - Ver roles
- `roles.crear` - Crear roles
- `roles.editar` - Editar roles
- `roles.eliminar` - Eliminar roles
- `roles.asignar` - Asignar roles

#### **Usuarios**
- `usuarios.ver` - Ver usuarios
- `usuarios.crear` - Crear usuarios
- `usuarios.editar` - Editar usuarios
- `usuarios.eliminar` - Eliminar usuarios
- `usuarios.activar` - Activar usuarios
- `usuarios.desactivar` - Desactivar usuarios

#### **Permisos**
- `permisos.ver` - Ver permisos
- `permisos.asignar` - Asignar permisos

### 📂 Categoría: Gestión
Permisos relacionados con la gestión de productos y contenido.

#### **Productos**
- `productos.ver` - Ver productos
- `productos.crear` - Crear productos
- `productos.editar` - Editar productos
- `productos.eliminar` - Eliminar productos
- `productos.publicar` - Publicar productos

#### **Categorías**
- `categorias.ver` - Ver categorías
- `categorias.crear` - Crear categorías
- `categorias.editar` - Editar categorías
- `categorias.eliminar` - Eliminar categorías

### 📂 Categoría: Reportes
Permisos relacionados con la generación y visualización de reportes.

#### **Ventas**
- `reportes.ventas.ver` - Ver reportes de ventas
- `reportes.ventas.exportar` - Exportar reportes de ventas

#### **Usuarios**
- `reportes.usuarios.ver` - Ver reportes de usuarios
- `reportes.usuarios.exportar` - Exportar reportes de usuarios

## API Endpoints

### Base URL
```
http://localhost:8000/api/v1/permissions
```

### Endpoints Disponibles

#### 1. Obtener todos los permisos
```http
GET /api/v1/permissions
```

**Respuesta:**
```json
{
    "success": true,
    "data": {
        "usuarios": {
            "ver": "usuarios.ver",
            "crear": "usuarios.crear",
            "editar": "usuarios.editar",
            "eliminar": "usuarios.eliminar",
            "exportar": "usuarios.exportar"
        },
        "productos": {
            "ver": "productos.ver",
            "crear": "productos.crear"
        }
    },
    "message": "Permisos obtenidos correctamente"
}
```

#### 2. Obtener permisos agrupados
```http
GET /api/v1/permissions/grouped
```

**Respuesta:**
```json
{
    "success": true,
    "data": {
        "usuarios": {
            "module_name": "Usuarios",
            "permissions": [
                {
                    "action": "ver",
                    "permission": "usuarios.ver",
                    "display_name": "Ver"
                }
            ]
        }
    },
    "message": "Permisos agrupados obtenidos correctamente"
}
```

#### 3. Obtener permisos en formato plano
```http
GET /api/v1/permissions/flat
```

**Respuesta:**
```json
{
    "success": true,
    "data": [
        {
            "module": "usuarios",
            "action": "ver",
            "permission": "usuarios.ver",
            "display_name": "Usuarios - Ver"
        }
    ],
    "message": "Permisos en formato plano obtenidos correctamente"
}
```

#### 4. Obtener categorías disponibles
```http
GET /api/v1/permissions/categories
```

**Respuesta:**
```json
{
    "success": true,
    "data": ["configuracion", "gestion", "reportes"],
    "message": "Categorías obtenidas correctamente"
}
```

#### 5. Obtener módulos disponibles
```http
GET /api/v1/permissions/modules
```

**Respuesta:**
```json
{
    "success": true,
    "data": ["roles", "usuarios", "permisos", "productos", "categorias", "ventas", "usuarios"],
    "message": "Módulos obtenidos correctamente"
}
```

#### 6. Obtener módulos de una categoría específica
```http
GET /api/v1/permissions/category/{category}/modules
```

**Ejemplo:**
```http
GET /api/v1/permissions/category/configuracion/modules
```

**Respuesta:**
```json
{
    "success": true,
    "data": ["roles", "usuarios", "permisos"],
    "message": "Módulos de la categoría 'configuracion' obtenidos correctamente"
}
```

#### 7. Obtener permisos de una categoría específica
```http
GET /api/v1/permissions/category/{category}
```

**Ejemplo:**
```http
GET /api/v1/permissions/category/configuracion
```

**Respuesta:**
```json
{
    "success": true,
    "data": {
        "roles": {
            "ver": "roles.ver",
            "crear": "roles.crear",
            "editar": "roles.editar",
            "eliminar": "roles.eliminar",
            "asignar": "roles.asignar"
        },
        "usuarios": {
            "ver": "usuarios.ver",
            "crear": "usuarios.crear",
            "editar": "usuarios.editar",
            "eliminar": "usuarios.eliminar",
            "activar": "usuarios.activar",
            "desactivar": "usuarios.desactivar"
        },
        "permisos": {
            "ver": "permisos.ver",
            "asignar": "permisos.asignar"
        }
    },
    "message": "Permisos de la categoría 'configuracion' obtenidos correctamente"
}
```

#### 8. Obtener permisos de un módulo específico
```http
GET /api/v1/permissions/module/{module}
```

**Ejemplo:**
```http
GET /api/v1/permissions/module/usuarios
```

**Respuesta:**
```json
{
    "success": true,
    "data": {
        "ver": "usuarios.ver",
        "crear": "usuarios.crear",
        "editar": "usuarios.editar",
        "eliminar": "usuarios.eliminar",
        "activar": "usuarios.activar",
        "desactivar": "usuarios.desactivar"
    },
    "message": "Permisos del módulo 'usuarios' obtenidos correctamente"
}
```

#### 9. Obtener permisos por tipo de acción
```http
GET /api/v1/permissions/action/{action}
```

**Ejemplo:**
```http
GET /api/v1/permissions/action/ver
```

**Respuesta:**
```json
{
    "success": true,
    "data": [
        {
            "module": "usuarios",
            "permission": "usuarios.ver",
            "display_name": "Usuarios - Ver"
        },
        {
            "module": "productos",
            "permission": "productos.ver",
            "display_name": "Productos - Ver"
        }
    ],
    "message": "Permisos de acción 'ver' obtenidos correctamente"
}
```

#### 10. Validar lista de permisos
```http
POST /api/v1/permissions/validate
```

**Body:**
```json
{
    "permissions": ["usuarios.ver", "productos.crear", "permiso.inexistente"]
}
```

**Respuesta:**
```json
{
    "success": true,
    "data": {
        "valid": ["usuarios.ver", "productos.crear"],
        "invalid": ["permiso.inexistente"]
    },
    "message": "Validación de permisos completada"
}
```

#### 11. Verificar si existe un permiso específico
```http
POST /api/v1/permissions/exists
```

**Body:**
```json
{
    "module": "usuarios",
    "action": "ver"
}
```

**Respuesta:**
```json
{
    "success": true,
    "data": {
        "exists": true,
        "permission_name": "usuarios.ver"
    },
    "message": "Verificación de permiso completada"
}
```

#### 12. Obtener permisos detallados agrupados
```http
GET /api/v1/permissions/detailed
```

**Respuesta:**
```json
{
    "success": true,
    "data": [
        {
            "categoria": "Configuración",
            "modulo": "usuarios",
            "permisos": {
                "ver": "usuarios.ver",
                "crear": "usuarios.crear",
                "editar": "usuarios.editar",
                "eliminar": "usuarios.eliminar",
                "activar": "usuarios.activar",
                "desactivar": "usuarios.desactivar"
            }
        }
    ],
    "message": "Permisos detallados obtenidos correctamente"
}
```

#### Métodos de Servicio Detallados

#### `getAllPermissionsDetailed(): array`
Obtiene todos los permisos agrupados por categoría y módulo con información detallada.

**Retorna:** Array con permisos organizados jerárquicamente

```php
[
    [
        'categoria' => 'Configuración',
        'modulo' => 'usuarios',
        'permisos' => [
            'ver' => 'usuarios.ver',
            'crear' => 'usuarios.crear',
            'editar' => 'usuarios.editar',
            'eliminar' => 'usuarios.eliminar',
            'activar' => 'usuarios.activar',
            'desactivar' => 'usuarios.desactivar'
        ]
    ],
    // ... más módulos
]
```

#### `getAllPermissionsFlatDetailed(): array`
Obtiene todos los permisos en formato plano con información completa de categoría, módulo, acción y nombre del permiso.

**Retorna:** Array con cada permiso individual y su información completa

```php
[
    [
        'categoria' => 'Configuración',
        'modulo' => 'usuarios', 
        'accion' => 'ver',
        'permiso' => 'usuarios.ver'
    ],
    // ... más permisos
]
```

#### 13. Obtener permisos en formato plano detallado
```http
GET /api/v1/permissions/flat-detailed
```

**Respuesta:**
```json
{
    "success": true,
    "data": [
        {
            "categoria": "Configuración",
            "modulo": "usuarios",
            "accion": "ver",
            "permiso": "usuarios.ver"
        },
        {
            "categoria": "Configuración",
            "modulo": "usuarios",
            "accion": "crear",
            "permiso": "usuarios.crear"
        }
    ],
    "message": "Permisos planos detallados obtenidos correctamente"
}
```

#### 14. Generar datos para seeder
```http
GET /api/v1/permissions/seeder-data
```

**Respuesta:**
```json
{
    "success": true,
    "data": [
        {
            "name": "usuarios.ver",
            "guard_name": "api",
            "module": "usuarios",
            "action": "ver",
            "display_name": "Usuarios - Ver",
            "created_at": "2024-01-01T00:00:00.000000Z",
            "updated_at": "2024-01-01T00:00:00.000000Z"
        }
    ],
    "message": "Datos para seeder generados correctamente"
}
```

## Uso del Servicio en Código

### Inyección de Dependencias

```php
use App\Services\PermissionService;

class MiControlador extends Controller
{
    public function __construct(private PermissionService $permissionService) {}
    
    public function miMetodo()
    {
        // Obtener todos los permisos
        $permisos = $this->permissionService->getAllPermissions();
        
        // Verificar si existe un permiso
        $existe = $this->permissionService->permissionExists('usuarios', 'ver');
        
        // Obtener permisos de un módulo
        $permisosUsuarios = $this->permissionService->getModulePermissions('usuarios');
    }
}
```

### Métodos Disponibles

#### Métodos Principales
```php
// Obtener todos los permisos
$permissionService->getAllPermissions();

// Obtener permisos en formato plano (con categorías)
$permissionService->getFlatPermissions();

// Obtener permisos agrupados por categoría y módulo
$permissionService->getGroupedPermissions();
```

#### Métodos de Categorías
```php
// Obtener todas las categorías
$permissionService->getCategories();

// Obtener permisos de una categoría específica
$permissionService->getCategoryPermissions('configuracion');

// Obtener módulos de una categoría específica
$permissionService->getCategoryModules('configuracion');
```

#### Métodos de Módulos
```php
// Obtener todos los módulos (de todas las categorías)
$permissionService->getAllModules();

// Obtener permisos de un módulo específico
$permissionService->getModulePermissions('usuarios');

// Obtener permisos agrupados por módulo (compatibilidad)
$permissionService->getModuleGroupedPermissions();
```

#### Métodos de Validación y Búsqueda
```php
// Verificar si existe un permiso (búsqueda automática en todas las categorías)
$permissionService->permissionExists('usuarios', 'ver');

// Verificar si existe un permiso en una categoría específica
$permissionService->permissionExists('usuarios', 'ver', 'configuracion');

// Obtener nombre completo del permiso
$permissionService->getPermissionName('usuarios', 'ver');

// Validar lista de permisos
$permissionService->validatePermissions(['usuarios.ver', 'productos.crear']);

// Obtener permisos por acción (con información de categoría)
$permissionService->getPermissionsByAction('ver');
```

#### Métodos de Utilidad
```php
// Generar datos para seeder (con información de categoría)
$permissionService->generateSeederData();
```

## Ejemplos de Uso con cURL

### Obtener todos los permisos
```bash
curl -X GET "http://localhost:8000/api/v1/permissions" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json"
```

### Validar permisos
```bash
curl -X POST "http://localhost:8000/api/v1/permissions/validate" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "permissions": ["usuarios.ver", "productos.crear", "permiso.inexistente"]
  }'
```

### Verificar existencia de permiso
```bash
curl -X POST "http://localhost:8000/api/v1/permissions/exists" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "module": "usuarios",
    "action": "ver"
  }'
```

## Agregar Nuevos Módulos y Permisos

Para agregar nuevos módulos o permisos, edita la constante `PERMISSIONS` en `PermissionService.php`:

```php
private const PERMISSIONS = [
    'nuevo_modulo' => [
        'ver' => 'nuevo_modulo.ver',
        'crear' => 'nuevo_modulo.crear',
        'editar' => 'nuevo_modulo.editar',
        'eliminar' => 'nuevo_modulo.eliminar',
        'accion_personalizada' => 'nuevo_modulo.accion_personalizada',
    ],
    // ... otros módulos
];
```

## Integración con Sistemas de Roles

Este sistema está diseñado para integrarse fácilmente con paquetes como Spatie Laravel Permission:

```php
// Crear permisos usando los datos del servicio
$permissionService = new PermissionService();
$seederData = $permissionService->generateSeederData();

foreach ($seederData as $permissionData) {
    Permission::create([
        'name' => $permissionData['name'],
        'guard_name' => $permissionData['guard_name'],
        // otros campos personalizados
    ]);
}
```

## Notas Importantes

1. **Centralización**: Todos los permisos están centralizados en una sola clase
2. **Escalabilidad**: Fácil agregar nuevos módulos y permisos
3. **Consistencia**: Nomenclatura consistente para todos los permisos
4. **Flexibilidad**: Múltiples formatos de salida para diferentes necesidades
5. **Validación**: Métodos integrados para validar permisos
6. **API RESTful**: Endpoints completos para gestión desde frontend

---

*Documentación del Sistema de Permisos - Laravel API*