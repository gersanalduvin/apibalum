# Módulo de Recibos — Endpoints Nuevos

## Prefijo
- `Base`: `/api/v1/recibos`

## 1) Obtener período(s) lectivo(s) con planes de pago activos
- `Ruta`: `GET /api/v1/recibos/catalogos/periodos-planes-pago`
- `Permiso`: `check.permissions:recibos.index`
- `Parámetros (query)`:
  - `periodo_lectivo_id` (opcional, integer): si se envía, retorna solo ese período con sus planes activos; si no, lista todos los períodos y sus planes activos
- `Respuesta (cuando se envía periodo_lectivo_id)`
```json
{
  "success": true,
  "data": {
    "periodo": {
      "id": 5,
      "uuid": "UUID-PERIODO",
      "nombre": "2025"
    },
    "planes_pago_activos": [
      {
        "id": 1,
        "uuid": "UUID-PLAN",
        "nombre": "Plan General 2025",
        "estado": true,
        "estado_text": "Activo",
        "periodo_lectivo": { "id": 5, "nombre": "2025" },
        "total_detalles": 10,
        "total_importe": 1200
      }
    ]
  },
  "message": "Período lectivo y planes de pago activos obtenidos exitosamente"
}
```
- `Respuesta (sin periodo_lectivo_id)`
```json
{
  "success": true,
  "data": [
    {
      "periodo": { "id": 5, "uuid": "...", "nombre": "2025" },
      "planes_pago_activos": [ { "id": 1, "uuid": "...", "nombre": "Plan 2025", "estado": true, "estado_text": "Activo", "periodo_lectivo": { "id": 5, "nombre": "2025" }, "total_detalles": 10, "total_importe": 1200 } ]
    },
    {
      "periodo": { "id": 6, "uuid": "...", "nombre": "2026" },
      "planes_pago_activos": [ ... ]
    }
  ],
  "message": "Períodos lectivos con planes de pago activos obtenidos exitosamente"
}
```
- `Errores`:
```json
{ "success": false, "message": "Período lectivo no encontrado" }
{ "success": false, "message": "Error al obtener períodos y planes de pago: <detalle>" }
```

## 2) Crear alumno y aplicar plan de pago (opcional)
- `Ruta`: `POST /api/v1/recibos/alumnos`
- `Permiso`: `check.permissions:recibos.store`
- `Body (JSON)`:
```json
{
  "email": "alumno@example.com",
  "primer_nombre": "Juan",
  "segundo_nombre": "Carlos",
  "primer_apellido": "Pérez",
  "segundo_apellido": "García",
  "fecha_nacimiento": "2015-04-20",
  "sexo": "M",
  "plan_pago_id": 1
}
```
- `Notas`:
  - `plan_pago_id` es opcional; si se envía, se aplican todos los detalles del plan al alumno en `users_aranceles`.
  - El usuario se crea con `tipo_usuario = 'alumno'`.
- `Respuesta (con plan aplicado)`
```json
{
  "success": true,
  "data": {
    "alumno": { "id": 123, "email": "alumno@example.com", "primer_nombre": "Juan", "primer_apellido": "Pérez", "sexo": "M", "tipo_usuario": "alumno" },
    "plan_pago_aplicacion": {
      "registros_creados": 5,
      "registros_omitidos": 0,
      "total_detalles": 5
    }
  },
  "message": "Alumno creado exitosamente y plan de pago aplicado"
}
```
- `Respuesta (sin plan)`
```json
{
  "success": true,
  "data": {
    "alumno": { "id": 123, "email": "alumno@example.com", "primer_nombre": "Juan", "primer_apellido": "Pérez", "sexo": "M", "tipo_usuario": "alumno" },
    "plan_pago_aplicacion": null
  },
  "message": "Alumno creado exitosamente"
}
```
- `Errores de validación (422)`
```json
{
  "success": false,
  "message": "Errores de validación",
  "errors": { "email": ["El email ya está registrado"], "sexo": ["El sexo debe ser M (Masculino) o F (Femenino)"] }
}
```
- `Errores de negocio (400/500)`
```json
{ "success": false, "message": "Error al crear alumno y aplicar plan: <detalle>" }
```

## Middleware de Permisos
- `recibos.index`: requerido para consultas de catálogos y reportes.
- `recibos.store`: requerido para creación de registros.

## Referencias Técnicas
- Controlador: `app/Http/Controllers/Api/V1/ReciboController.php`
  - `periodoPlanesPago` en `app/Http/Controllers/Api/V1/ReciboController.php:152`
  - `crearAlumnoConPlan` en `app/Http/Controllers/Api/V1/ReciboController.php:191`
- Rutas: `routes/api/v1/recibos.php`
  - `GET /catalogos/periodos-planes-pago` en `routes/api/v1/recibos.php:17`
  - `POST /alumnos` en `routes/api/v1/recibos.php:18`

## Notas
- Las respuestas siguen el estándar JSON del proyecto: `{ success, data, message }`.
- Los planes de pago listados se filtran por `estado = true` (activos).