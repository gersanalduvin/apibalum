# Frontend: Modal de Historial de Cambios

## Objetivo
- Mostrar en un modal, por cada registro de un modelo, el usuario que lo creó con fecha y hora, y su historial de cambios desde la tabla `audits`.

## Permiso requerido
- `auditoria.ver`

## Endpoint
- `GET /api/v1/audits/{model}/{id}/summary`
- Path params:
  - `model`: alias del modelo permitido (ej.: `users`, `users_grupos`, `config_grado`, `config_turnos`, `config_modalidad`, `config_grupos`, `categoria`).
  - `id`: identificador del registro.

## Respuesta 200
```
{
  "success": true,
  "data": {
    "creado_por": {
      "id": 5,
      "nombre": "Ana García",
      "created_at": "2025-11-29T17:05:11.000Z"
    },
    "historial": [
      {
        "event": "updated",
        "usuario": { "id": 2, "nombre": "Luis Pérez" },
        "fecha": "2025-11-29T17:10:05.000Z",
        "cambios": [
          { "campo": "grado_id", "de": 4, "a": 5 },
          { "campo": "turno_id", "de": 2, "a": 3 }
        ]
      }
    ]
  },
  "message": "Resumen de auditoría"
}
```

## Estados del modal
- `loading`: mientras se realiza la petición.
- `loaded`: datos recibidos; renderizar secciones:
  - Encabezado: "Creado por {nombre} el {fecha}" (formatear ISO → `dd/mm/yyyy HH:mm`).
  - Lista de cambios: por cada elemento de `historial`, mostrar `event`, `fecha`, y una tabla con `campo`, `de`, `a`.
- `error`: mostrar mensaje si la respuesta es `success=false` o status `4xx/5xx`.

## Ejemplo (JS/TS con fetch)
```ts
async function openAuditModal(model: string, id: number) {
  setModalState({ open: true, loading: true, error: null, data: null })
  try {
    const res = await fetch(`/api/v1/audits/${model}/${id}/summary`, {
      headers: { 'Accept': 'application/json' }
    })
    if (!res.ok) throw new Error(`HTTP ${res.status}`)
    const json = await res.json()
    if (!json.success) throw new Error(json.message || 'Error en auditoría')
    setModalState({ open: true, loading: false, error: null, data: json.data })
  } catch (e) {
    setModalState({ open: true, loading: false, error: String(e), data: null })
  }
}
```

## Render sugerido
- Encabezado del modal:
  - `Creado por: {data.creado_por.nombre ?? 'N/D'}`
  - `Fecha: {formatISO(data.creado_por.created_at)}`
- Cuerpo:
  - Para cada ítem de `historial`:
    - Subtítulo: `{event} • {fecha}`
    - Tabla cambios: columnas `Campo`, `Antes`, `Después`.
- Pie: botón cerrar.

## Consideraciones
- Campos excluidos del diff: `updated_at`, `created_at`, `deleted_at` (ya filtrados en la API).
- Si necesitas mostrar nombres de relaciones (ej.: `grado_id → nombre`), resuélvelo en el frontend mapeando ids a nombres disponibles en cache o vía endpoints de catálogos.
- Controlar crecimiento del modal: paginación no es necesaria; para listados muy extensos, usar scroll interno.

## Errores comunes
- `403`: falta de permiso `auditoria.ver`.
- `404`: modelo o id inexistente.
- `400`: alias de modelo no permitido.

## Buenas prácticas
- Invocar el endpoint solo al abrir el modal, no en cada render.
- Al cerrar el modal, limpiar el estado para evitar datos residuales.
- Formatear fechas consistentemente (`dd/mm/yyyy HH:mm`).
