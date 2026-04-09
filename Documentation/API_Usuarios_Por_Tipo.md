# API de Usuarios por Tipo - Documentación

## Información General

Esta documentación describe la nueva estructura de la API de usuarios organizada por tipos específicos: **Administrativos**, **Docentes**, **Alumnos** y **Familias**. Cada tipo de usuario tiene sus propias rutas y permisos granulares.

## Estado de Implementación

✅ **COMPLETADO**: Todos los métodos definidos en las rutas han sido implementados en el `UserController.php`

### Métodos Implementados:
- **Métodos Generales**: `activate()`, `deactivate()`, `changePasswordAdmin()`
- **Administrativos**: CRUD completo + exportar/importar
- **Docentes**: CRUD completo + asignar materias + ver horarios + exportar/importar
- **Alumnos**: CRUD completo + expediente + notas + matricular + trasladar + retirar + exportar/importar
- **Familias**: CRUD completo + vincular/desvincular estudiantes + ver estudiantes + exportar/importar

### Características de la Implementación:
- ✅ Respuestas JSON estandarizadas
- ✅ Manejo de errores con try-catch
- ✅ Validaciones de entrada
- ✅ Integración con `UserService` y `FileUploadService`
- ✅ Códigos de estado HTTP apropiados
- ✅ Mensajes en español

## Estructura de la Tabla Users

### Campos de la Tabla Users

La tabla `users` contiene una amplia variedad de campos organizados por categorías para manejar información completa de usuarios del sistema educativo:

#### **Campos Básicos del Sistema**
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer | Sí | ID único del usuario (auto-incremental) |
| `name` | string(255) | No | Nombre completo del usuario |
| `email` | string(255) | Sí | Email único del usuario |
| `password` | string | Sí | Contraseña encriptada |
| `superadmin` | boolean | No | Indica si es superadministrador |
| `role_id` | integer | No | ID del rol asignado |
| `tipo_usuario` | enum | Sí | Tipo: 'administrativo', 'superuser', 'alumno', 'docente', 'familia' |

#### **Campos de Auditoría**
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `created_by` | integer | ID del usuario que creó el registro |
| `updated_by` | integer | ID del usuario que modificó el registro |
| `deleted_by` | integer | ID del usuario que eliminó el registro |
| `deleted_at` | timestamp | Fecha de eliminación (soft delete) |
| `cambios` | json | Historial de cambios del registro |
| `created_at` | timestamp | Fecha de creación |
| `updated_at` | timestamp | Fecha de última actualización |

#### **Datos Personales del Usuario**
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `codigo_mined` | string | Código MINED único |
| `codigo_unico` | string | Código único del estudiante |
| `primer_nombre` | string | Primer nombre |
| `segundo_nombre` | string | Segundo nombre |
| `primer_apellido` | string | Primer apellido |
| `segundo_apellido` | string | Segundo apellido |
| `fecha_nacimiento` | date | Fecha de nacimiento |
| `lugar_nacimiento` | string | Lugar de nacimiento |
| `sexo` | enum('M','F') | Sexo del usuario |
| `correo_notificaciones` | string | Email para notificaciones |

#### **Gestión de Fotos**
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `foto` | string | Nombre del archivo de foto |
| `foto_url` | string | URL completa de la foto |
| `foto_path` | string | Ruta de almacenamiento |
| `foto_uploaded_at` | timestamp | Fecha de subida de la foto |

#### **Información de la Madre**
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `nombre_madre` | string | Nombre completo de la madre |
| `fecha_nacimiento_madre` | date | Fecha de nacimiento de la madre |
| `cedula_madre` | string | Cédula de identidad de la madre |
| `religion_madre` | string | Religión de la madre |
| `estado_civil_madre` | enum | Estados: 'soltera', 'casada', 'divorciada', 'viuda', 'union_libre', 'separada', 'otro' |
| `telefono_madre` | string | Teléfono principal |
| `telefono_claro_madre` | string | Teléfono Claro |
| `telefono_tigo_madre` | string | Teléfono Tigo |
| `direccion_madre` | string | Dirección de residencia |
| `barrio_madre` | string | Barrio de residencia |
| `ocupacion_madre` | string | Ocupación laboral |
| `lugar_trabajo_madre` | string | Lugar de trabajo |
| `telefono_trabajo_madre` | string | Teléfono del trabajo |

#### **Información del Padre**
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `nombre_padre` | string | Nombre completo del padre |
| `fecha_nacimiento_padre` | date | Fecha de nacimiento del padre |
| `cedula_padre` | string | Cédula de identidad del padre |
| `religion_padre` | string | Religión del padre |
| `estado_civil_padre` | enum | Estados: 'soltero', 'casado', 'divorciado', 'viudo', 'union_libre', 'separado', 'otro' |
| `telefono_padre` | string | Teléfono principal |
| `telefono_claro_padre` | string | Teléfono Claro |
| `telefono_tigo_padre` | string | Teléfono Tigo |
| `direccion_padre` | string | Dirección de residencia |
| `barrio_padre` | string | Barrio de residencia |
| `ocupacion_padre` | string | Ocupación laboral |
| `lugar_trabajo_padre` | string | Lugar de trabajo |
| `telefono_trabajo_padre` | string | Teléfono del trabajo |

#### **Información del Responsable**
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `nombre_responsable` | string | Nombre del responsable |
| `cedula_responsable` | string | Cédula del responsable |
| `telefono_responsable` | string | Teléfono del responsable |
| `direccion_responsable` | string | Dirección del responsable |

#### **Datos Familiares**
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `cantidad_hijos` | integer | Número de hijos en la familia |
| `lugar_en_familia` | string | Posición del estudiante en la familia |
| `personas_hogar` | text | Personas que viven en el hogar |
| `encargado_alumno` | string | Persona encargada del alumno |
| `contacto_emergencia` | string | Contacto de emergencia |
| `telefono_emergencia` | string | Teléfono de emergencia |
| `metodos_disciplina` | text | Métodos de disciplina utilizados |
| `pasatiempos_familiares` | text | Actividades familiares |

#### **Área Médica/Psicológica/Social**
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `personalidad` | text | Descripción de la personalidad |
| `parto` | enum('natural','cesarea') | Tipo de parto |
| `sufrimiento_fetal` | boolean | Sufrimiento fetal durante el parto |
| `edad_gateo` | integer | Edad en meses cuando gateó |
| `edad_caminar` | integer | Edad en meses cuando caminó |
| `edad_hablar` | integer | Edad en meses cuando habló |
| `habilidades` | text | Habilidades especiales |
| `pasatiempos` | text | Pasatiempos del estudiante |
| `preocupaciones` | text | Preocupaciones de los padres |
| `juegos_preferidos` | text | Juegos favoritos |

#### **Área Social**
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `se_relaciona_familiares` | boolean | Se relaciona bien con familiares |
| `establece_relacion_coetaneos` | boolean | Se relaciona con compañeros |
| `evita_contacto_personas` | boolean | Evita contacto con personas |
| `especifique_evita_personas` | text | Especificación de evitación |
| `evita_lugares_situaciones` | boolean | Evita lugares o situaciones |
| `especifique_evita_lugares` | text | Especificación de lugares evitados |
| `respeta_figuras_autoridad` | boolean | Respeta figuras de autoridad |

#### **Área Comunicativa**
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `atiende_cuando_llaman` | boolean | Atiende cuando lo llaman |
| `es_capaz_comunicarse` | boolean | Es capaz de comunicarse |
| `comunica_palabras` | boolean | Se comunica con palabras |
| `comunica_señas` | boolean | Se comunica con señas |
| `comunica_llanto` | boolean | Se comunica llorando |
| `dificultad_expresarse` | boolean | Tiene dificultad para expresarse |
| `especifique_dificultad_expresarse` | text | Especificación de dificultades |
| `dificultad_comprender` | boolean | Dificultad para comprender |
| `especifique_dificultad_comprender` | text | Especificación de comprensión |
| `atiende_orientaciones` | boolean | Atiende orientaciones |

#### **Área Psicológica**
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `estado_animo_general` | enum | Estados: 'alegre', 'triste', 'enojado', 'indiferente' |
| `tiene_fobias` | boolean | Presenta fobias |
| `generador_fobia` | text | Qué genera la fobia |
| `tiene_agresividad` | boolean | Presenta agresividad |
| `tipo_agresividad` | enum('encubierta','directa') | Tipo de agresividad |

#### **Área Médica Detallada**
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `patologias_detalle` | text | Detalle de patologías |
| `consume_farmacos` | boolean | Consume medicamentos |
| `farmacos_detalle` | text | Detalle de medicamentos |
| `tiene_alergias` | boolean | Presenta alergias |
| `causas_alergia` | text | Causas de las alergias |
| `alteraciones_patron_sueño` | boolean | Alteraciones del sueño |
| `se_duerme_temprano` | boolean | Se duerme temprano |
| `se_duerme_tarde` | boolean | Se duerme tarde |
| `apnea_sueño` | boolean | Presenta apnea del sueño |
| `pesadillas` | boolean | Tiene pesadillas |
| `enuresis_secundaria` | boolean | Enuresis secundaria |
| `alteraciones_apetito_detalle` | boolean | Alteraciones del apetito |
| `aversion_alimentos` | text | Alimentos que rechaza |
| `reflujo` | boolean | Presenta reflujo |
| `alimentos_favoritos` | text | Alimentos favoritos |
| `alteracion_vision` | boolean | Alteraciones de la visión |
| `alteracion_audicion` | boolean | Alteraciones de la audición |
| `alteracion_tacto` | boolean | Alteraciones del tacto |
| `especifique_alteraciones_sentidos` | text | Especificación de alteraciones |

#### **Alteraciones Físicas**
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `alteraciones_oseas` | boolean | Alteraciones óseas |
| `alteraciones_musculares` | boolean | Alteraciones musculares |
| `pie_plano` | boolean | Presenta pie plano |

#### **Datos Especiales**
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `diagnostico_medico` | text | Diagnóstico médico |
| `referido_escuela_especial` | boolean | Referido a escuela especial |
| `trajo_epicrisis` | boolean | Trajo epicrisis médica |
| `presenta_diagnostico_matricula` | boolean | Presenta diagnóstico en matrícula |

#### **Información de Retiro**
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `fecha_retiro` | date | Fecha de retiro del centro |
| `retiro_notificado` | boolean | Retiro fue notificado |
| `motivo_retiro` | text | Motivo del retiro |
| `informacion_retiro_adicional` | text | Información adicional del retiro |

#### **Observaciones y Firma**
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `observaciones` | text | Observaciones generales |
| `nombre_persona_firma` | string | Nombre de quien firma |
| `cedula_firma` | string | Cédula de quien firma |

### Validaciones Importantes

#### **Campos Obligatorios**
- `primer_nombre`: Requerido, máximo 100 caracteres
- `segundo_nombre`: Requerido, máximo 100 caracteres  
- `primer_apellido`: Requerido, máximo 100 caracteres
- `segundo_apellido`: Requerido, máximo 100 caracteres
- `email`: Requerido, formato email válido, único
- `tipo_usuario`: Requerido, valores válidos

#### **Validaciones Especiales**
- `sexo`: Solo acepta 'M' o 'F'
- `fecha_nacimiento`: Debe ser anterior a hoy
- `estado_civil_madre/padre`: Valores específicos por género
- `parto`: Solo 'natural' o 'cesarea'
- `estado_animo_general`: 'alegre', 'triste', 'enojado', 'indiferente'
- `tipo_agresividad`: 'encubierta' o 'directa'
- Campos de edad: Números enteros positivos (en meses)
- Campos booleanos: `true` o `false`
- `foto_file`: Imagen (jpeg, jpg, png, webp) máximo 5MB

#### **Generación Automática**
- Si no se proporciona `email`, se genera automáticamente: `{primer_nombre}.{primer_apellido}@cempp.edu.ni`
- Los caracteres se normalizan (sin acentos ni espacios)

## Estructura de Permisos

### Categoría: `usuarios`

Los permisos están organizados bajo la categoría `usuarios` con subcategorías por tipo:

- `usuarios.administrativos.*`
- `usuarios.docentes.*`
- `usuarios.alumnos.*`
- `usuarios.familias.*`

## Rutas por Tipo de Usuario

### 1. Usuarios Administrativos

**Base URL:** `/api/v1/usuarios/administrativos`

#### Permisos Necesarios:
- `usuarios.administrativos.ver` - Ver usuarios administrativos
- `usuarios.administrativos.crear` - Crear usuarios administrativos
- `usuarios.administrativos.editar` - Editar usuarios administrativos
- `usuarios.administrativos.eliminar` - Eliminar usuarios administrativos
- `usuarios.administrativos.activar` - Activar usuarios administrativos
- `usuarios.administrativos.desactivar` - Desactivar usuarios administrativos
- `usuarios.administrativos.cambiar_password` - Cambiar contraseña de usuarios administrativos
- `usuarios.administrativos.exportar` - Exportar usuarios administrativos
- `usuarios.administrativos.importar` - Importar usuarios administrativos

#### Endpoints Disponibles:

| Método | Endpoint | Permiso Requerido | Descripción |
|--------|----------|-------------------|-------------|
| GET | `/` | `usuarios.administrativos.ver` | Listar usuarios administrativos |
| POST | `/` | `usuarios.administrativos.crear` | Crear usuario administrativo |
| GET | `/{id}` | `usuarios.administrativos.ver` | Ver usuario administrativo específico |
| PUT | `/{id}` | `usuarios.administrativos.editar` | Actualizar usuario administrativo |
| DELETE | `/{id}` | `usuarios.administrativos.eliminar` | Eliminar usuario administrativo |
| PUT | `/change-password` | Autenticación | Cambiar propia contraseña |
| PUT | `/{id}/activate` | `usuarios.administrativos.activar` | Activar usuario |
| PUT | `/{id}/deactivate` | `usuarios.administrativos.desactivar` | Desactivar usuario |
| PUT | `/{id}/change-password-admin` | `usuarios.administrativos.cambiar_password` | Cambiar contraseña como admin |
| POST | `/{id}/upload-photo` | `usuarios.administrativos.editar` | Subir foto de perfil |
| DELETE | `/{id}/delete-photo` | `usuarios.administrativos.editar` | Eliminar foto de perfil |
| GET | `/export` | `usuarios.administrativos.exportar` | Exportar datos |
| POST | `/import` | `usuarios.administrativos.importar` | Importar datos |

---

### 2. Usuarios Docentes

**Base URL:** `/api/v1/usuarios/docentes`

#### Permisos Necesarios:
- `usuarios.docentes.ver` - Ver usuarios docentes
- `usuarios.docentes.crear` - Crear usuarios docentes
- `usuarios.docentes.editar` - Editar usuarios docentes
- `usuarios.docentes.eliminar` - Eliminar usuarios docentes
- `usuarios.docentes.activar` - Activar usuarios docentes
- `usuarios.docentes.desactivar` - Desactivar usuarios docentes
- `usuarios.docentes.cambiar_password` - Cambiar contraseña de usuarios docentes
- `usuarios.docentes.asignar_materias` - Asignar materias a docentes
- `usuarios.docentes.ver_horarios` - Ver horarios de docentes
- `usuarios.docentes.exportar` - Exportar usuarios docentes
- `usuarios.docentes.importar` - Importar usuarios docentes

#### Endpoints Disponibles:

| Método | Endpoint | Permiso Requerido | Descripción |
|--------|----------|-------------------|-------------|
| GET | `/` | `usuarios.docentes.ver` | Listar usuarios docentes |
| POST | `/` | `usuarios.docentes.crear` | Crear usuario docente |
| GET | `/{id}` | `usuarios.docentes.ver` | Ver usuario docente específico |
| PUT | `/{id}` | `usuarios.docentes.editar` | Actualizar usuario docente |
| DELETE | `/{id}` | `usuarios.docentes.eliminar` | Eliminar usuario docente |
| PUT | `/change-password` | Autenticación | Cambiar propia contraseña |
| PUT | `/{id}/activate` | `usuarios.docentes.activar` | Activar usuario |
| PUT | `/{id}/deactivate` | `usuarios.docentes.desactivar` | Desactivar usuario |
| PUT | `/{id}/change-password-admin` | `usuarios.docentes.cambiar_password` | Cambiar contraseña como admin |
| POST | `/{id}/upload-photo` | `usuarios.docentes.editar` | Subir foto de perfil |
| DELETE | `/{id}/delete-photo` | `usuarios.docentes.editar` | Eliminar foto de perfil |
| POST | `/{id}/assign-subjects` | `usuarios.docentes.asignar_materias` | Asignar materias |
| GET | `/{id}/schedules` | `usuarios.docentes.ver_horarios` | Ver horarios |
| GET | `/export` | `usuarios.docentes.exportar` | Exportar datos |
| POST | `/import` | `usuarios.docentes.importar` | Importar datos |

---

### 3. Usuarios Alumnos

**Base URL:** `/api/v1/usuarios/alumnos`

#### Permisos Necesarios:
- `usuarios.alumnos.ver` - Ver usuarios alumnos
- `usuarios.alumnos.crear` - Crear usuarios alumnos
- `usuarios.alumnos.editar` - Editar usuarios alumnos
- `usuarios.alumnos.eliminar` - Eliminar usuarios alumnos
- `usuarios.alumnos.activar` - Activar usuarios alumnos
- `usuarios.alumnos.desactivar` - Desactivar usuarios alumnos
- `usuarios.alumnos.cambiar_password` - Cambiar contraseña de usuarios alumnos
- `usuarios.alumnos.ver_expediente` - Ver expediente de alumnos
- `usuarios.alumnos.editar_expediente` - Editar expediente de alumnos
- `usuarios.alumnos.ver_notas` - Ver notas de alumnos
- `usuarios.alumnos.matricular` - Matricular alumnos
- `usuarios.alumnos.trasladar` - Trasladar alumnos
- `usuarios.alumnos.retirar` - Retirar alumnos
- `usuarios.alumnos.subir_foto` - Subir foto de alumnos
- `usuarios.alumnos.eliminar_foto` - Eliminar foto de alumnos
- `usuarios.alumnos.exportar` - Exportar usuarios alumnos
- `usuarios.alumnos.importar` - Importar usuarios alumnos

#### Endpoints Disponibles:

| Método | Endpoint | Permiso Requerido | Descripción |
|--------|----------|-------------------|-------------|
| GET | `/` | `usuarios.alumnos.ver` | Listar usuarios alumnos |
| POST | `/` | `usuarios.alumnos.crear` | Crear usuario alumno |
| GET | `/{id}` | `usuarios.alumnos.ver` | Ver usuario alumno específico |
| PUT | `/{id}` | `usuarios.alumnos.editar` | Actualizar usuario alumno |
| DELETE | `/{id}` | `usuarios.alumnos.eliminar` | Eliminar usuario alumno |
| PUT | `/change-password` | Autenticación | Cambiar propia contraseña |
| PUT | `/{id}/activate` | `usuarios.alumnos.activar` | Activar usuario |
| PUT | `/{id}/deactivate` | `usuarios.alumnos.desactivar` | Desactivar usuario |
| PUT | `/{id}/change-password-admin` | `usuarios.alumnos.cambiar_password` | Cambiar contraseña como admin |
| POST | `/{id}/upload-photo` | `usuarios.alumnos.subir_foto` | Subir foto de perfil |
| DELETE | `/{id}/delete-photo` | `usuarios.alumnos.eliminar_foto` | Eliminar foto de perfil |
| GET | `/{id}/expediente` | `usuarios.alumnos.ver_expediente` | Ver expediente |
| PUT | `/{id}/expediente` | `usuarios.alumnos.editar_expediente` | Editar expediente |
| GET | `/{id}/notas` | `usuarios.alumnos.ver_notas` | Ver notas |
| POST | `/{id}/matricular` | `usuarios.alumnos.matricular` | Matricular alumno |
| POST | `/{id}/trasladar` | `usuarios.alumnos.trasladar` | Trasladar alumno |
| POST | `/{id}/retirar` | `usuarios.alumnos.retirar` | Retirar alumno |
| GET | `/export` | `usuarios.alumnos.exportar` | Exportar datos |
| POST | `/import` | `usuarios.alumnos.importar` | Importar datos |

---

### 4. Usuarios Familias

**Base URL:** `/api/v1/usuarios/familias`

#### Permisos Necesarios:
- `usuarios.familias.ver` - Ver usuarios familias
- `usuarios.familias.crear` - Crear usuarios familias
- `usuarios.familias.editar` - Editar usuarios familias
- `usuarios.familias.eliminar` - Eliminar usuarios familias
- `usuarios.familias.activar` - Activar usuarios familias
- `usuarios.familias.desactivar` - Desactivar usuarios familias
- `usuarios.familias.cambiar_password` - Cambiar contraseña de usuarios familias
- `usuarios.familias.vincular_estudiante` - Vincular estudiante a familia
- `usuarios.familias.desvincular_estudiante` - Desvincular estudiante de familia
- `usuarios.familias.ver_estudiantes` - Ver estudiantes vinculados
- `usuarios.familias.exportar` - Exportar usuarios familias
- `usuarios.familias.importar` - Importar usuarios familias

#### Endpoints Disponibles:

| Método | Endpoint | Permiso Requerido | Descripción |
|--------|----------|-------------------|-------------|
| GET | `/` | `usuarios.familias.ver` | Listar usuarios familias |
| POST | `/` | `usuarios.familias.crear` | Crear usuario familia |
| GET | `/{id}` | `usuarios.familias.ver` | Ver usuario familia específico |
| PUT | `/{id}` | `usuarios.familias.editar` | Actualizar usuario familia |
| DELETE | `/{id}` | `usuarios.familias.eliminar` | Eliminar usuario familia |
| PUT | `/change-password` | Autenticación | Cambiar propia contraseña |
| PUT | `/{id}/activate` | `usuarios.familias.activar` | Activar usuario |
| PUT | `/{id}/deactivate` | `usuarios.familias.desactivar` | Desactivar usuario |
| PUT | `/{id}/change-password-admin` | `usuarios.familias.cambiar_password` | Cambiar contraseña como admin |
| POST | `/{id}/upload-photo` | `usuarios.familias.editar` | Subir foto de perfil |
| DELETE | `/{id}/delete-photo` | `usuarios.familias.editar` | Eliminar foto de perfil |
| POST | `/{id}/vincular-estudiante` | `usuarios.familias.vincular_estudiante` | Vincular estudiante |
| DELETE | `/{id}/desvincular-estudiante/{student_id}` | `usuarios.familias.desvincular_estudiante` | Desvincular estudiante |
| GET | `/{id}/estudiantes` | `usuarios.familias.ver_estudiantes` | Ver estudiantes vinculados |
| GET | `/export` | `usuarios.familias.exportar` | Exportar datos |
| POST | `/import` | `usuarios.familias.importar` | Importar datos |

---

## Ejemplos de Respuestas

### Respuesta Exitosa (200 OK)
```json
{
    "success": true,
    "data": {
        "id": 1,
        "primer_nombre": "Juan",
        "primer_apellido": "Pérez",
        "email": "juan.perez@example.com",
        "tipo_usuario": "administrativo",
        "activo": true,
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z"
    },
    "message": "Usuario obtenido exitosamente"
}
```

### Respuesta de Error de Permisos (403 Forbidden)
```json
{
    "success": false,
    "message": "No tienes permisos para realizar esta acción",
    "errors": {
        "permission": "usuarios.administrativos.ver"
    }
}
```

### Respuesta de Validación (422 Unprocessable Entity)
```json
{
    "success": false,
    "message": "Errores de validación",
    "errors": {
        "primer_nombre": ["El campo primer nombre es obligatorio"],
        "email": ["El campo email debe ser una dirección válida"]
    }
}
```

---

## Middleware de Permisos

Todas las rutas utilizan el middleware `check.permissions` que verifica si el usuario autenticado tiene el permiso específico requerido.

### Ejemplo de uso:
```php
Route::get('/', [UserController::class, 'indexAdministrativos'])
    ->middleware('check.permissions:usuarios.administrativos.ver');
```

---

## Notas Importantes

1. **Autenticación Requerida**: Todas las rutas requieren autenticación mediante Sanctum (`auth:sanctum`)

2. **Permisos Granulares**: Cada acción tiene su permiso específico, permitiendo control fino sobre las funcionalidades

3. **Compatibilidad**: Las rutas originales de usuarios (`/api/v1/users`) se mantienen para compatibilidad con código existente

4. **Estructura Consistente**: Todas las rutas siguen la misma estructura y convenciones de nomenclatura

5. **Middleware Personalizado**: El middleware `check.permissions` debe estar registrado en el kernel de la aplicación

6. **Tipos de Usuario**: Los tipos válidos son: `administrativo`, `docente`, `alumno`, `familia`, `superuser`

7. **Soft Delete**: Todos los usuarios utilizan eliminación lógica (soft delete)

8. **Auditoría**: Todos los cambios se registran con campos de auditoría (`created_by`, `updated_by`, `deleted_by`)

---

## Archivos de Rutas

- `routes/api/v1/usuarios-administrativos.php`
- `routes/api/v1/usuarios-docentes.php`
- `routes/api/v1/usuarios-alumnos.php`
- `routes/api/v1/usuarios-familias.php`

Todos registrados en `routes/api.php` bajo el prefijo `v1` con middleware de autenticación.