# API Inventario Categorías

## Descripción
API para la gestión de categorías de inventario. Permite crear, consultar, actualizar, eliminar y navegar la jerarquía de categorías. Esta versión elimina los campos `orden`, `nivel`, `ruta_jerarquia` y `propiedades_adicionales` del esquema.

## Autenticación y Permisos
Todos los endpoints requieren autenticación (`auth:sanctum`) y validación de permisos (`check.permissions`).

Permisos del módulo:
- `inventario.categorias.index` — Listar categorías
- `inventario.categorias.show` — Ver categoría
- `inventario.categorias.create` — Crear categoría
- `inventario.categorias.update` — Editar categoría
- `inventario.categorias.delete` — Eliminar categoría
- `inventario.categorias.sync` — Sincronización y utilitarios

## Base URL
`/api/v1/categorias`

## Esquema de Categoría (actualizado)
- `id` (int) — Identificador interno
- `uuid` (string) — Identificador único universal (auto)
- `codigo` (string, unique) — Código de la categoría
- `nombre` (string) — Nombre de la categoría
- `descripcion` (string, nullable) — Descripción
- `categoria_padre_id` (int, nullable) — ID de la categoría padre
- `activo` (bool, default: true) — Estado
- Auditoría y sincronización: `created_by`, `updated_by`, `deleted_by`, `cambios`, `is_synced`, `synced_at`, `updated_locally_at`, `version`, `created_at`, `updated_at`, `deleted_at`

Campos eliminados en esta versión:
- `orden` — Eliminado
- `nivel` — Eliminado
- `ruta_jerarquia` — Eliminado
- `propiedades_adicionales` — Eliminado

## Endpoints

### 1) Listar categorías (paginado)
**GET** `/api/v1/categorias`
- Permiso: `inventario.categorias.index`
- Parámetros: `per_page` (opcional), `activo` (opcional), `categoria_padre_id` (opcional), `search` (opcional)
- Respuesta 200:
```json
{
  "success": true,
  "data": { "current_page": 1, "data": [ /* categorías */ ], "per_page": 15, "total": 42 },
  "message": "Categorías obtenidas exitosamente"
}
```

### 2) Listar todas las categorías
**GET** `/api/v1/categorias/all/list`
- Permiso: `inventario.categorias.index`
- Respuesta 200: arreglo de categorías

### 3) Árbol de categorías
**GET** `/api/v1/categorias/tree/hierarchy`
- Permiso: `inventario.categorias.index`
- Respuesta 200: categorías raíz con `categoriasHijas` anidadas

### 4) Categorías raíz
**GET** `/api/v1/categorias/roots/list`
- Permiso: `inventario.categorias.index`

### 5) Hijas de una categoría
**GET** `/api/v1/categorias/{categoriaId}/children`
- Permiso: `inventario.categorias.index`
- Ruta param: `categoriaId` (int)

### 6) Categorías activas
**GET** `/api/v1/categorias/active/list`
- Permiso: `inventario.categorias.index`

### 7) Búsqueda de categorías
**GET** `/api/v1/categorias/search/query?q=texto`
- Permiso: `inventario.categorias.index`
- Query: `q` (string, requerido)

### 8) Ver categoría
**GET** `/api/v1/categorias/{id}`
- Permiso: `inventario.categorias.show`
- Respuesta 200: detalle de la categoría

### 9) Crear categoría
**POST** `/api/v1/categorias`
- Permiso: `inventario.categorias.create`
- Body:
```json
{
  "codigo": "CAT-001",
  "nombre": "Accesorios",
  "descripcion": "Accesorios varios",
  "categoria_padre_id": null,
  "activo": true
}
```
- Respuesta 201: categoría creada

### 10) Actualizar categoría
**PUT** `/api/v1/categorias/{id}`
- Permiso: `inventario.categorias.update`
- Body (parcial):
```json
{
  "nombre": "Accesorios y repuestos",
  "descripcion": "Actualización de nombre y descripción",
  "categoria_padre_id": 3,
  "activo": true
}
```
- Respuesta 200: categoría actualizada

### 11) Eliminar categoría
**DELETE** `/api/v1/categorias/{id}`
- Permiso: `inventario.categorias.delete`
- Respuesta 200: operación exitosa (soft delete)

### 12) Cambiar estado
**PATCH** `/api/v1/categorias/{id}/toggle-status`
- Permiso: `inventario.categorias.update`

### 13) Estadísticas
**GET** `/api/v1/categorias/statistics/summary`
- Permiso: `inventario.categorias.index`

### 14) Sincronización (offline)
**POST** `/api/v1/categorias/sync/data`
- Permiso: `inventario.categorias.sync`

### Endpoints deprecados por cambios de esquema
- **GET** `/api/v1/categorias/level/{nivel}` — Deprecado (campo `nivel` eliminado)
- **POST** `/api/v1/categorias/reorder/items` — Deprecado (campo `orden` eliminado)

## Notas de compatibilidad
- Requests que incluyan `orden`, `nivel`, `ruta_jerarquia` o `propiedades_adicionales` deben actualizarse; dichos campos ya no son válidos.
- La jerarquía se navega por `categoria_padre_id` y relaciones `categoriasHijas`.

## Ejemplos de respuesta (árbol de categorías)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "codigo": "CAT-ROOT",
      "nombre": "Categoría Raíz",
      "categoria_padre_id": null,
      "activo": true,
      "categorias_hijas": [
        { "id": 2, "codigo": "CAT-001", "nombre": "Accesorios", "categoria_padre_id": 1, "activo": true }
      ]
    }
  ],
  "message": "Árbol de categorías obtenido exitosamente"
}
```