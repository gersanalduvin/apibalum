# API Organizar Listas

## Descripción
Módulo para organizar listas de alumnos por período lectivo, grado y turno. Incluye catálogos, filtros, exportación a PDF/Excel, consulta de grupos y asignación masiva.

## Base URL
`/api/v1/organizar`

## Permiso requerido
- `organizar.lista`

## Endpoints

### 1. Catálogos
`GET /api/v1/organizar/catalogos`
- Devuelve `periodos_lectivos`, `grados` y `turnos` ordenados por `orden` si existe.

### 2. Listado de Alumnos
`GET /api/v1/organizar/alumnos`
- Parámetros: `periodo_lectivo_id` (opcional), `grado_id` (opcional), `turno_id` (opcional)
- Orden: `sexo`, `primer_nombre`, `segundo_nombre`, `primer_apellido`, `segundo_apellido`
- Respuesta: `[ { user_id, nombre_completo, sexo } ]`

### 3. PDF de Alumnos
`GET /api/v1/organizar/alumnos/pdf`
- Mismos parámetros que el listado
- Devuelve archivo PDF con la lista de alumnos.

### 4. Excel (CSV) de Alumnos
`GET /api/v1/organizar/alumnos/excel`
- Mismos parámetros que el listado
- Devuelve archivo CSV (`lista_alumnos.csv`).

### 5. Grupos por Filtros
`GET /api/v1/organizar/grupos`
- Parámetros: `periodo_lectivo_id`, `grado_id`, `turno_id` (opcionales)
- Respuesta: `[ { id, periodo_lectivo_id, grado, seccion, turno } ]`

### 6. Asignación de Grupo
`POST /api/v1/organizar/asignar-grupo`
- Body:
```json
{
  "alumnos": [1,2,3],
  "grupo_id": 10
}
```
- Efecto: actualiza `users_grupos` para cada alumno según el `periodo_lectivo_id` del grupo; asigna `grupo_id`, `grado_id` y `turno_id` del grupo.

Alternativamente, asignación por alumno:

```json
{
  "asignaciones": [
    { "user_id": 1, "grupo_id": 10 },
    { "user_id": 2, "grupo_id": 11 }
  ]
}
```
- Efecto: procesa cada par `user_id`/`grupo_id` y actualiza `users_grupos` usando el `periodo_lectivo_id` del grupo indicado para cada alumno.

## Respuestas estándar
- Éxito: `{ "success": true, "data": ..., "message": "Operación exitosa" }`
- Error: `{ "success": false, "message": "Error", "errors": {} }`