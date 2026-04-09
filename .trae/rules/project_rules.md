- Información General
1. Framework: Laravel (solo para APIs)
2. Respuestas: JSON exclusivamente
3. Principios: SOLID
4. Estructura: CRUDs con rutas separadas

-Estructura de Directorios
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── V1/
│   │   │   │   ├── UserController.php
│   │   │   │   ├── ProductController.php
│   │   │   │   └── ...
│   ├── Requests/
│   │   ├── Api/
│   │   │   ├── V1/
│   │   │   │   ├── UserRequest.php
│   │   │   │   ├── ProductRequest.php
│   │   │   │   └── ...
│   └── Services/
│       ├── UserService.php
│       ├── ProductService.php
│       └── ...
└── Repositories/
    ├── UserRepository.php
    ├── ProductRepository.php
    └── ...

- Configuración de Rutas
routes/api/
routes/
├── api/
│   ├── v1/
│   │   ├── users.php
│   │   ├── products.php
│   │   ├── categories.php
│   │   └── ...
│   └── api.php

--Ejemplo de archivo de rutas (routes/api/v1/users.php)
<?php

use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::get('/{id}', [UserController::class, 'show']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::delete('/{id}', [UserController::class, 'destroy']);
});

--Registrar rutas en routes/api.php
<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    require __DIR__ . '/v1/users.php';
    require __DIR__ . '/v1/products.php';
    // Agregar más archivos aquí
});

-Principios SOLID Aplicados
1. Single Responsibility (Controladores)
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\UserRequest;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(private UserService $userService) {}
    
    public function index(): JsonResponse
    {
        $users = $this->userService->getAllUsers();
        return response()->json([
            'success' => true,
            'data' => $users,
            'message' => 'Users retrieved successfully'
        ]);
    }
    
    public function store(UserRequest $request): JsonResponse
    {
        $user = $this->userService->createUser($request->validated());
        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'User created successfully'
        ], 201);
    }
}

2. Form Requests para Validación
<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
        ];
    }
    
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'errors' => $validator->errors()
        ], 422));
    }
}

3. Service Layer
<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Exception;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function __construct(private UserRepository $userRepository) {}
    
    public function getAllUsers()
    {
        return $this->userRepository->getAll();
    }
    
    public function createUser(array $data)
    {
        try {
            DB::beginTransaction();
            
            $data['password'] = bcrypt($data['password']);
            $user = $this->userRepository->create($data);
            
            DB::commit();
            return $user;
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

4. Repository Pattern
<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserRepository
{
    public function __construct(private User $model) {}
    
    public function getAll(): Collection
    {
        return $this->model->all();
    }
    
    public function create(array $data): User
    {
        return $this->model->create($data);
    }
    
    public function find(int $id): ?User
    {
        return $this->model->find($id);
    }
    
    public function update(int $id, array $data): bool
    {
        return $this->model->where('id', $id)->update($data);
    }
    
    public function delete(int $id): bool
    {
        return $this->model->destroy($id);
    }
}

-Respuestas JSON Estándar
--Success Response (200 OK)
{
    "success": true,
    "data": {...},
    "message": "Operación exitosa"
}

--Created Response (201 Created)
{
    "success": true,
    "data": {...},
    "message": "Registro creado exitosamente"
}

--Error Response (400-500)
{
    "success": false,
    "message": "Error message",
    "errors": {...}
}

--Validation Error Response (422 Unprocessable Entity)
{
    "success": false,
    "message": "Validation errors",
    "errors": {
        "email": ["El campo email es obligatorio"]
    }
}

--Not Found Response (404 Not Found)
{
    "success": false,
    "message": "Recurso no encontrado"
}

-Configuración Adicional
1. Exception Handler (app/Exceptions/Handler.php)
public function register(): void
{
    $this->renderable(function (Throwable $e, $request) {
        if ($request->is('api/*')) {
            $statusCode = $this->isHttpException($e) ? $e->getStatusCode() : 500;
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], $statusCode);
        }
    });
}

2. Base Controller (app/Http/Controllers/Controller.php)

<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
    
    protected function successResponse($data, $message = 'Success', $statusCode = 200)
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message
        ], $statusCode);
    }
    
    protected function errorResponse($message, $errors = [], $statusCode = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $statusCode);
    }
}

-Comandos de Instalación y Setup
# Crear estructura de directorios
mkdir -p app/Http/{Controllers,Requests}/Api/V1
mkdir -p app/Services app/Repositories
mkdir -p routes/api/v1

# Crear directorios para requests
mkdir -p app/Http/Requests/Api/V1

# Crear controlador base con métodos de respuesta
php artisan make:controller Controller

-Convenciones de Código
1. Namespaces: App\Http\Controllers\Api\V1
2. Nomenclatura: PascalCase para clases, camelCase para métodos y variables
3. Type Hinting: Siempre usar tipos de retorno y type hints
4. Inyección de Dependencias: Usar constructor injection
5. Métodos de Controlador: index(devolvera datos paginados), store, show, update, destroy, getall(devolvera todos los registros) 
6. Mensajes en español: Todos los mensajes de respuesta en español

-Flujo de Desarrollo para un Nuevo CRUD
1. Crear Modelo y Migración, todos los modelos y migraciones incluiran los campos: created_by, updated_by, deleted_by, deleted_at que permita softdelete. 
2. **OBLIGATORIO: Aplicar trait Auditable** - Todos los modelos DEBEN incluir automáticamente el trait Auditable.
3. Crear Repository (app/Repositories/)
4. Crear Service (app/Services/)
5. Crear Form Request (app/Http/Requests/Api/V1/)
6. Crear Controller (app/Http/Controllers/Api/V1/)
7. Crear los permisos dentro del servicio de permisos.
8. Crear Archivo de Rutas (routes/api/v1/)
9. Aplicar middleware a cada ruta
10. Registrar Rutas en routes/api.php
11. Documentar las api, con los permisos necesarios, crear un archivo de ayuda dentro de la carpeta Documenation.
12. Registrar el modelo en AuditController: Todo nuevo modelo creado debe agregarse al mapeo `private array $models` de `App\Http\Controllers\Api\V1\AuditController` para habilitar la consulta de historial de auditoría.

-Sistema de Auditoría Automática
**IMPORTANTE: Todos los modelos nuevos DEBEN incluir automáticamente el trait Auditable para auditoría completa.



1. **Estructura del Modelo con Auditoría**
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class NuevoModelo extends Model
{
    use SoftDeletes, Auditable;
    
    protected $fillable = [
        'campo1',
        'campo2',
        'created_by',
        'updated_by',
        'deleted_by'
    ];
    
    protected $casts = [
        'deleted_at' => 'datetime'
    ];
    
    // El trait Auditable se configura automáticamente
    // No es necesario configurar manualmente los eventos
}

2. **Migración con Campos de Auditoría**
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nuevo_modelo', function (Blueprint $table) {
            $table->id();
            $table->string('campo1');
            $table->string('campo2');
            
            // Campos obligatorios de auditoría
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            // Índices para auditoría
            $table->index(['created_by', 'updated_by', 'deleted_by']);
        });
    }
};

3. **Comandos de Auditoría Disponibles**
# Aplicar trait Auditable a modelos existentes
php artisan audit:update-models

# Verificar configuración de auditoría
php artisan audit:test --cleanup

# Generar modelo con auditoría automática (comando personalizado)
php artisan make:auditable-model NombreModelo

# Consultar auditorías de un modelo
php artisan audit:query ModelName --limit=10

4. **Configuración Automática del Trait Auditable**
- **Eventos auditables**: created, updated, deleted (configurados automáticamente)
- **Campos excluidos**: updated_at, created_at, deleted_at
- **Registro de cambios**: Se almacena en tabla 'audits'
- **Usuario actual**: Se obtiene automáticamente del contexto de autenticación

5. **Métodos Disponibles en Modelos con Auditable**
$modelo = NuevoModelo::find(1);

// Obtener todas las auditorías
$auditorias = $modelo->audits;

// Obtener auditorías por evento
$creaciones = $modelo->audits()->where('event', 'created')->get();
$actualizaciones = $modelo->audits()->where('event', 'updated')->get();

// Obtener cambios específicos
$cambiosRecientes = $modelo->getRecentChanges(10);
$cambiosPorUsuario = $modelo->getChangesByUser($userId);

6. **Reglas de Implementación**
- **NUNCA** crear un modelo sin el trait Auditable
- **SIEMPRE** incluir los campos de auditoría en las migraciones
- **OBLIGATORIO** usar el comando personalizado para generar modelos
- **VERIFICAR** que la auditoría funcione con php artisan audit:test
- **DOCUMENTAR** los cambios auditables en la documentación de la API

# Reglas de Sincronización de Datos (Solo si se utiliza modo offline)

Este documento define las **reglas del sistema de sincronización** entre los clientes **PWA** y el **servidor Laravel**, para ser utilizadas dentro del proyecto en **Trae.ai** o documentación técnica.

---

## 🎯 Objetivo
Permitir que la aplicación funcione **sin conexión a internet**, almacenando temporalmente los datos de usuarios localmente, y sincronizándolos con el servidor cuando se restablezca la conexión, sin pérdida de información ni duplicados.

---

## ⚙️ Campos involucrados

| Campo | Tipo | Descripción |
|--------|------|-------------|
| `uuid` | `uuid()` | Identificador único universal. Se genera en el cliente al crear el registro y se mantiene en todas las bases. |
| `is_synced` | `boolean()` | Indica si el registro está sincronizado (`true`) o pendiente de envío (`false`). |
| `synced_at` | `timestamp()` | Fecha y hora de la última sincronización exitosa con el servidor. |
| `updated_locally_at` | `timestamp()` | Fecha y hora de la última modificación realizada sin conexión. |
| `version` | `integer()` | Incrementa cada vez que se modifica el registro, permitiendo controlar versiones. |
| `deleted_at` | `softDeletes()` | Control de eliminación lógica para evitar pérdidas durante sincronización diferida. |

---

## 🔄 Flujo de sincronización

### 1️⃣ Creación offline
- El usuario puede crear o editar registros sin conexión.  
- Se genera un `uuid` único y se marca el registro como `is_synced = false`.  
- Se almacena en la base de datos local (IndexedDB o SQLite).  

### 2️⃣ Envío al servidor (Cliente → Servidor)
- Al recuperar conexión, la app envía todos los registros con `is_synced = false`.
- El servidor compara `uuid` y `version`:
  - Si no existe, lo crea.
  - Si ya existe, compara versiones para decidir si actualiza o ignora.

### 3️⃣ Descarga de actualizaciones (Servidor → Cliente)
- El cliente puede solicitar solo los registros modificados después de su última sincronización:
  ```http
  GET /api/usuarios?updated_after=YYYY-MM-DDTHH:MM:SSZ
  ```
- El servidor devuelve los registros nuevos o modificados.

### 4️⃣ Confirmación
- Una vez completada la sincronización, el cliente actualiza:
  - `is_synced = true`
  - `synced_at = fecha_actual`

---

## ⚔️ Resolución de conflictos

Cuando varios clientes modifican el mismo registro antes de sincronizar:

| Estrategia | Descripción |
|-------------|-------------|
| **Servidor gana** | El servidor conserva la versión más reciente según `updated_at`. |
| **Cliente gana** | La versión del cliente sobrescribe la del servidor. Útil para apps monousuario. |
| **Merge inteligente** | Se combinan campos si las ediciones no se sobreponen. |
| **Manual** | Si el conflicto no puede resolverse automáticamente, se marca para revisión manual. |

---

## 🧱 Ejemplo de sincronización

1. Cliente crea registro offline → `is_synced = false`  
2. Cliente edita → `version = 2`  
3. Cliente se conecta → envía datos al servidor  
4. Servidor compara versiones y guarda los más recientes  
5. Cliente marca registro como sincronizado

---

## 🧩 Recomendaciones para implementación

- Usar **UUID v4** generado localmente (`crypto.randomUUID()` o `Str::uuid()`).
- No depender del `id` autoincremental del servidor.
- Mantener una copia local de los `deleted_at` para sincronizar eliminaciones.
- Registrar cada sincronización con logs (`synced_at`) para auditoría.
- Aplicar políticas de resolución de conflictos según el tipo de usuario (único o multiusuario).

---

## 🧠 Notas finales

Estas reglas solo aplican **si la aplicación usa modo offline**.  
Si todos los registros se gestionan directamente en línea, estos campos (`uuid`, `is_synced`, etc.) pueden omitirse.
