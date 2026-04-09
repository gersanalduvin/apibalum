# API Usuarios

## Información General
- **Endpoint Base**: `/api/v1/users`
- **Autenticación**: Bearer Token requerido
- **Respuestas**: JSON exclusivamente
- **Principios**: SOLID

## Permisos Necesarios

| Acción | Permiso Requerido | Descripción |
|--------|------------------|-------------|
| Listar usuarios | `usuarios.ver` | Ver listado de usuarios |
| Ver usuario específico | `usuarios.ver` | Ver detalles de un usuario |
| Crear usuario | `usuarios.crear` | Crear nuevos usuarios |
| Actualizar usuario | `usuarios.editar` | Modificar usuarios existentes |
| Eliminar usuario | `usuarios.eliminar` | Eliminar usuarios |
| Cambiar contraseña | `usuarios.cambiar_password` | Cambiar contraseña de usuarios |

## Endpoints Disponibles

### 1. Obtener Todos los Usuarios
```http
GET /api/v1/users/getall
```

**Permisos**: `usuarios.ver`

**Respuesta Exitosa (200)**:
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "primer_nombre": "Juan",
            "segundo_nombre": "Carlos",
            "primer_apellido": "Pérez",
            "segundo_apellido": "García",
            "nombre_completo": "Juan Carlos Pérez García",
            "email": "juan.perez@cempp.edu.ni",
            "tipo_usuario": "estudiante",
            "activo": true,
            // ... otros campos
        }
    ],
    "message": "Usuarios obtenidos exitosamente"
}
```

### 2. Obtener Usuarios Paginados
```http
GET /api/v1/users?page=1&per_page=10&search=juan&tipo_usuario=estudiante
```

**Permisos**: `usuarios.ver`

**Parámetros de Consulta**:
- `page` (opcional): Número de página (default: 1)
- `per_page` (opcional): Registros por página (default: 15)
- `search` (opcional): Búsqueda por nombre, apellido o email
- `tipo_usuario` (opcional): Filtrar por tipo (estudiante, docente, familia)

**Respuesta Exitosa (200)**:
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [...],
        "first_page_url": "http://localhost/api/v1/users?page=1",
        "from": 1,
        "last_page": 5,
        "last_page_url": "http://localhost/api/v1/users?page=5",
        "links": [...],
        "next_page_url": "http://localhost/api/v1/users?page=2",
        "path": "http://localhost/api/v1/users",
        "per_page": 15,
        "prev_page_url": null,
        "to": 15,
        "total": 75
    },
    "message": "Usuarios obtenidos exitosamente"
}
```

### 3. Obtener Solo Estudiantes
```http
GET /api/v1/users/students
```

**Permisos**: `usuarios.ver`

### 4. Obtener Solo Docentes
```http
GET /api/v1/users/teachers
```

**Permisos**: `usuarios.ver`

### 5. Obtener Solo Familiares
```http
GET /api/v1/users/families
```

**Permisos**: `usuarios.ver`

### 6. Obtener Usuario Específico
```http
GET /api/v1/users/{id}
```

**Permisos**: `usuarios.ver`

**Respuesta Exitosa (200)**:
```json
{
    "success": true,
    "data": {
        "id": 1,
        "primer_nombre": "Juan",
        "segundo_nombre": "Carlos",
        "primer_apellido": "Pérez",
        "segundo_apellido": "García",
        "nombre_completo": "Juan Carlos Pérez García",
        "email": "juan.perez@cempp.edu.ni",
        "tipo_usuario": "estudiante",
        "activo": true,
        // ... todos los campos del estudiante
    },
    "message": "Usuario obtenido exitosamente"
}
```

### 7. Crear Usuario
```http
POST /api/v1/users
```

**Permisos**: `usuarios.crear`

**Campos del Cuerpo de la Petición**:

#### Campos Básicos (Requeridos)
```json
{
    "primer_nombre": "Juan",
    "segundo_nombre": "Carlos",
    "primer_apellido": "Pérez", 
    "segundo_apellido": "García",
    "tipo_usuario": "estudiante"
}
```

#### Campos Adicionales del Usuario (Todos Opcionales)
```json
{
    // ======= Datos básicos del usuario =======
    "codigo_mined": "2024-001",
    "codigo_unico": "CEMPP-2024-001",
    "primer_nombre": "Juan",
    "segundo_nombre": "Carlos",
    "primer_apellido": "Pérez",
    "segundo_apellido": "García",
    "fecha_nacimiento": "2010-05-15",
    "lugar_nacimiento": "Managua, Nicaragua",
    "sexo": "M", // M o F
    "foto": "usuarios/fotos/1/foto_usuario_1_2024-01-15_10-30-00_abc12345.jpg",
    "foto_url": "https://bucket-name.s3.amazonaws.com/usuarios/fotos/1/foto_usuario_1_2024-01-15_10-30-00_abc12345.jpg",
    "foto_path": "usuarios/fotos/1/foto_usuario_1_2024-01-15_10-30-00_abc12345.jpg",
    "foto_uploaded_at": "2024-01-15T10:30:00.000000Z",
    "correo_notificaciones": "notificaciones@cempp.edu.ni",
    
    // ======= Datos de la madre =======
    "nombre_madre": "María García",
    "fecha_nacimiento_madre": "1980-05-20",
    "cedula_madre": "001-200580-0001B",
    "religion_madre": "Católica",
    "estado_civil_madre": "casada", // soltera, casada, divorciada, viuda, union_libre, separada, otro
    "telefono_madre": "8777-7777",
    "telefono_claro_madre": "8666-6666",
    "telefono_tigo_madre": "8555-5555",
    "direccion_madre": "Barrio Central, Casa #123",
    "barrio_madre": "Barrio Central",
    "ocupacion_madre": "Enfermera",
    "lugar_trabajo_madre": "Hospital Bertha Calderón",
    "telefono_trabajo_madre": "2222-2222",
    
    // ======= Datos del padre =======
    "nombre_padre": "Carlos Pérez",
    "fecha_nacimiento_padre": "1975-03-10",
    "cedula_padre": "001-100375-0001C",
    "religion_padre": "Católica",
    "estado_civil_padre": "casado", // soltero, casado, divorciado, viudo, union_libre, separado, otro
    "telefono_padre": "8444-4444",
    "telefono_claro_padre": "8333-3333",
    "telefono_tigo_padre": "8222-2222",
    "direccion_padre": "Barrio Central, Casa #123",
    "barrio_padre": "Barrio Central",
    "ocupacion_padre": "Ingeniero",
    "lugar_trabajo_padre": "Empresa ABC",
    "telefono_trabajo_padre": "2333-3333",
    
    // ======= Responsable =======
    "nombre_responsable": "María García",
    "cedula_responsable": "001-200580-0001B",
    "telefono_responsable": "8666-6666",
    "direccion_responsable": "Barrio Central, Casa #123",
    
    // ======= Datos familiares =======
    "cantidad_hijos": 3,
    "lugar_en_familia": "Primer hijo",
    "personas_hogar": "Padre, madre, 2 hermanos menores, abuela materna",
    "encargado_alumno": "Madre",
    "contacto_emergencia": "María García",
    "telefono_emergencia": "8666-6666",
    "metodos_disciplina": "Diálogo, tiempo fuera, restricción de privilegios",
    "pasatiempos_familiares": "Ver películas, jugar fútbol, ir a la playa",
    
    // ======= Área médica / psicológica / social =======
    "personalidad": "Extrovertido, alegre, colaborador, algo tímido con extraños",
    "parto": "natural", // natural o cesarea
    "sufrimiento_fetal": false,
    "edad_gateo": 8, // meses
    "edad_caminar": 12, // meses
    "edad_hablar": 18, // meses
    "habilidades": "Buena coordinación motora, facilidad para matemáticas, creatividad artística",
    "pasatiempos": "Dibujar, jugar fútbol, leer cuentos, videojuegos educativos",
    "preocupaciones": "Timidez excesiva en grupos grandes, dificultad para concentrarse",
    "juegos_preferidos": "Fútbol, juegos de construcción, rompecabezas",
    
    // ======= Área social =======
    "se_relaciona_familiares": true,
    "establece_relacion_coetaneos": true,
    "evita_contacto_personas": false,
    "especifique_evita_personas": null,
    "evita_lugares_situaciones": false,
    "especifique_evita_lugares": null,
    "respeta_figuras_autoridad": true,
    
    // ======= Área comunicativa =======
    "atiende_cuando_llaman": true,
    "es_capaz_comunicarse": true,
    "comunica_palabras": true,
    "comunica_señas": false,
    "comunica_llanto": false,
    "dificultad_expresarse": false,
    "especifique_dificultad_expresarse": null,
    "dificultad_comprender": false,
    "especifique_dificultad_comprender": null,
    "atiende_orientaciones": true,
    
    // ======= Área psicológica =======
    "estado_animo_general": "alegre", // alegre, triste, enojado, indiferente
    "tiene_fobias": false,
    "generador_fobia": null,
    "tiene_agresividad": false,
    "tipo_agresividad": null, // encubierta o directa
    
    // ======= Área médica detallada =======
    "patologias_detalle": "Asma leve controlada",
    "consume_farmacos": true,
    "farmacos_detalle": "Salbutamol inhalador según necesidad",
    "tiene_alergias": true,
    "causas_alergia": "Polen, ácaros del polvo",
    "alteraciones_patron_sueño": false,
    "se_duerme_temprano": true,
    "se_duerme_tarde": false,
    "apnea_sueño": false,
    "pesadillas": false,
    "enuresis_secundaria": false,
    "alteraciones_apetito_detalle": false,
    "aversion_alimentos": "Verduras verdes, pescado",
    "reflujo": false,
    "alimentos_favoritos": "Pizza, pollo, frutas, helado",
    "alteracion_vision": false,
    "alteracion_audicion": false,
    "alteracion_tacto": false,
    "especifique_alteraciones_sentidos": null,
    
    // ======= Alteraciones físicas adicionales =======
    "alteraciones_oseas": false,
    "alteraciones_musculares": false,
    "pie_plano": false,
    
    // ======= Datos especiales =======
    "diagnostico_medico": "Asma bronquial leve",
    "referido_escuela_especial": false,
    "trajo_epicrisis": true,
    "presenta_diagnostico_matricula": true,
    
    // ======= Retiro =======
    "fecha_retiro": null,
    "retiro_notificado": null,
    "motivo_retiro": null,
    "informacion_retiro_adicional": null,
    
    // ======= Observaciones =======
    "observaciones": "Estudiante con buen rendimiento académico. Requiere seguimiento por asma.",
    
    // ======= Firma =======
    "nombre_persona_firma": "María García",
    "cedula_firma": "001-200580-0001B",
    
    // ======= Tipo de usuario =======
    "tipo_usuario": "alumno" // administrativo, superuser, alumno, docente, familia
}
```

**Respuesta Exitosa (201)**:
```json
{
    "success": true,
    "data": {
        "id": 1,
        "primer_nombre": "Juan",
        "segundo_nombre": "Carlos",
        "primer_apellido": "Pérez",
        "segundo_apellido": "García",
        "nombre_completo": "Juan Carlos Pérez García",
        "email": "juan.perez@cempp.edu.ni",
        "tipo_usuario": "estudiante",
        // ... otros campos
    },
    "message": "Usuario creado exitosamente"
}
```

### 8. Actualizar Usuario
```http
PUT /api/v1/users/{id}
```

**Permisos**: `usuarios.editar`

**Cuerpo de la Petición**: Mismos campos que crear usuario (todos opcionales)

**Respuesta Exitosa (200)**:
```json
{
    "success": true,
    "data": {
        // Usuario actualizado
    },
    "message": "Usuario actualizado exitosamente"
}
```

### 9. Eliminar Usuario
```http
DELETE /api/v1/users/{id}
```

**Permisos**: `usuarios.eliminar`

**Respuesta Exitosa (200)**:
```json
{
    "success": true,
    "data": null,
    "message": "Usuario eliminado exitosamente"
}
```

### 10. Cambiar Contraseña
```http
POST /api/v1/users/{id}/change-password
```

**Permisos**: `usuarios.cambiar_password`

**Cuerpo de la Petición**:
```json
{
    "current_password": "contraseña_actual",
    "new_password": "nueva_contraseña",
    "new_password_confirmation": "nueva_contraseña"
}
```

**Respuesta Exitosa (200)**:
```json
{
    "success": true,
    "data": null,
    "message": "Contraseña cambiada exitosamente"
}
```

## Validaciones Específicas

### Campos Obligatorios
- `primer_nombre`: Requerido, máximo 255 caracteres
- `primer_apellido`: Requerido, máximo 255 caracteres
- `email`: Requerido, formato email válido, único en el sistema

### Campos con Validaciones Especiales
- `codigo_mined`: Único si se proporciona
- `codigo_unico`: Único si se proporciona
- `sexo`: Debe ser 'M' o 'F'
- `tipo_usuario`: Debe ser 'administrativo', 'superuser', 'alumno', 'docente' o 'familia'
- `fecha_nacimiento`: Formato de fecha válido (YYYY-MM-DD)
- `fecha_nacimiento_madre`: Formato de fecha válido (YYYY-MM-DD)
- `fecha_nacimiento_padre`: Formato de fecha válido (YYYY-MM-DD)
- `estado_civil_madre`: soltera, casada, divorciada, viuda, union_libre, separada, otro
- `estado_civil_padre`: soltero, casado, divorciado, viudo, union_libre, separado, otro
- `parto`: natural o cesarea
- `estado_animo_general`: alegre, triste, enojado, indiferente
- `tipo_agresividad`: encubierta o directa
- `edad_gateo`, `edad_caminar`, `edad_hablar`: Números enteros positivos (meses)
- `cantidad_hijos`: Número entero positivo
- Campos booleanos: `true` o `false`
- Emails adicionales: Formato email válido si se proporcionan
- `foto`: Archivo de imagen (jpg, jpeg, png, gif) máximo 5MB

## Funcionalidades Automáticas

### Generación Automática de Email
Si no se proporciona un email, el sistema genera uno automáticamente:
- Formato: `{primer_nombre}.{primer_apellido}@cempp.edu.ni`
- Se normalizan los caracteres (sin acentos ni espacios)
- Se garantiza unicidad agregando números si es necesario

### Generación de Nombre Completo
Se genera automáticamente concatenando:
`{primer_nombre} {segundo_nombre} {primer_apellido} {segundo_apellido}`

### Contraseña por Defecto
Si no se proporciona contraseña: `cempp123`

### Historial de Cambios
Todos los cambios se registran en el campo `cambios` con:
- Valor anterior y nuevo
- Email del usuario que realizó el cambio
- Fecha y hora del cambio

## Respuestas de Error

### Error de Validación (422)
```json
{
    "success": false,
    "message": "Errores de validación",
    "errors": {
        "primer_nombre": ["El campo primer nombre es obligatorio"],
        "email": ["El email ya está en uso"]
    }
}
```

### Error de Permisos (403)
```json
{
    "success": false,
    "message": "No tienes permisos para realizar esta acción"
}
```

### Usuario No Encontrado (404)
```json
{
    "success": false,
    "message": "Usuario no encontrado"
}
```

### Error del Servidor (500)
```json
{
    "success": false,
    "message": "Error interno del servidor"
}
```

## Endpoints de Manejo de Fotos

### 8. Subir Foto de Usuario
```http
POST /api/v1/users/{id}/upload-photo
```

**Permisos**: `usuarios.editar`

**Parámetros**:
- `{id}`: ID del usuario

**Body (multipart/form-data)**:
```
foto_file: [archivo de imagen] (requerido)
```

**Validaciones**:
- Tipos permitidos: jpeg, jpg, png, webp
- Tamaño máximo: 5MB
- Debe ser un archivo de imagen válido

**Respuesta Exitosa (200)**:
```json
{
    "success": true,
    "data": {
        "id": 1,
        "foto_url": "https://bucket-name.s3.region.amazonaws.com/usuarios/fotos/1/foto_1234567890.jpg",
        "foto_path": "usuarios/fotos/1/foto_1234567890.jpg",
        "foto_uploaded_at": "2024-01-15T10:30:00.000000Z"
    },
    "message": "Foto subida exitosamente"
}
```

### 9. Eliminar Foto de Usuario
```http
DELETE /api/v1/users/{id}/delete-photo
```

**Permisos**: `usuarios.editar`

**Parámetros**:
- `{id}`: ID del usuario

**Respuesta Exitosa (200)**:
```json
{
    "success": true,
    "data": {
        "id": 1,
        "foto_url": null,
        "foto_path": null,
        "foto_uploaded_at": null
    },
    "message": "Foto eliminada exitosamente"
}
```

**Errores Específicos de Fotos**:

### Usuario Sin Foto (404)
```json
{
    "success": false,
    "message": "El usuario no tiene foto para eliminar"
}
```

### Error de Validación de Archivo (422)
```json
{
    "success": false,
    "message": "Errores de validación",
    "errors": {
        "foto_file": ["El archivo debe ser una imagen válida (jpeg, jpg, png, webp)"]
    }
}
```

### Error de Almacenamiento S3 (500)
```json
{
    "success": false,
    "message": "Error al procesar la imagen en el almacenamiento"
}
```

## Notas Importantes

1. **Tipos de Usuario**: El sistema maneja cinco tipos principales:
   - `administrativo`: Personal administrativo del centro
   - `superuser`: Administradores del sistema
   - `alumno`: Estudiantes del centro educativo
   - `docente`: Personal docente
   - `familia`: Familiares de estudiantes

2. **Campos Opcionales**: La mayoría de campos son opcionales para permitir registro gradual de información

3. **Soft Delete**: Los usuarios eliminados se marcan como eliminados pero no se borran físicamente

4. **Auditoría**: Todos los cambios quedan registrados con usuario y fecha en el campo `cambios`

5. **Campos de Auditoría**: Cada registro incluye:
   - `created_by`: Usuario que creó el registro
   - `updated_by`: Usuario que modificó el registro
   - `deleted_by`: Usuario que eliminó el registro
   - `deleted_at`: Fecha de eliminación (soft delete)
   - `cambios`: Historial completo de cambios en formato JSON

6. **Búsqueda**: La búsqueda funciona en nombre, apellidos y email

7. **Paginación**: Por defecto 15 registros por página, máximo 100

8. **Almacenamiento de Fotos**: 
   - Las fotos se almacenan en AWS S3 en el bucket configurado
   - Ruta de almacenamiento: `usuarios/fotos/{user_id}/`
   - Formatos soportados: JPEG, JPG, PNG, WebP
   - Tamaño máximo: 5MB por archivo
   - Las fotos anteriores se eliminan automáticamente al subir una nueva
   - Disponible para todos los tipos de usuario
   - Campos relacionados: `foto`, `foto_url`, `foto_path`, `foto_uploaded_at`

9. **Campos Especializados**:
   - **Área Médica**: Incluye información detallada sobre salud, alergias, medicamentos
   - **Área Psicológica**: Estado de ánimo, fobias, agresividad
   - **Área Social**: Relaciones familiares y sociales
   - **Área Comunicativa**: Habilidades de comunicación y comprensión
   - **Datos Familiares**: Información completa de padres, responsables y estructura familiar

10. **Validaciones Especiales**:
    - Estados civiles diferenciados por género (madre/padre)
    - Campos de edad en meses para desarrollo infantil
    - Campos booleanos para evaluaciones médicas y psicológicas
    - Enums específicos para categorizar información médica y social

---

## Endpoint Especial: Generar Ficha de Inscripción PDF

### Generar PDF de Ficha de Inscripción
```http
GET /api/v1/users-grupos/{id}/ficha-inscripcion-pdf
```

**NOTA IMPORTANTE**: Este endpoint se movió desde `/api/v1/usuarios/alumnos/{id}/ficha-inscripcion-pdf` a `/api/v1/users-grupos/{id}/ficha-inscripcion-pdf` para utilizar el ID de UsersGrupo en lugar del ID del usuario.

**Descripción**: Genera un PDF con la ficha de inscripción del alumno basado en el registro de matrícula (UsersGrupo).

**Permisos**: `usuarios.alumnos.ver`

**Parámetros de Ruta**:
- `id` (integer, requerido): ID del registro de UsersGrupo (matrícula del alumno)

**Respuesta Exitosa (200)**:
- **Content-Type**: `application/pdf`
- **Content-Disposition**: `attachment; filename="ficha_inscripcion_[nombre_alumno].pdf"`
- **Cuerpo**: Archivo PDF binario para descarga

**Estructura del PDF**:
El PDF incluye las siguientes 9 secciones principales:

1. **Datos Básicos del Alumno**:
   - Nombres y apellidos completos
   - Número ROC (código MINED)
   - Fecha de inscripción (fecha_matricula del UsersGrupo)
   - Nivel, grado, grupo, sección y turno (desde relaciones de UsersGrupo)
   - Período lectivo actual
   - Edad, código estudiantil, sexo
   - Lugar de nacimiento, nacionalidad
   - Dirección, teléfono, email

2. **Información Familiar**:
   - Datos completos de la madre
   - Datos completos del padre  
   - Datos del responsable (si aplica)
   - Información de familiares adicionales

3. **Contacto de Emergencia**:
   - Nombre, teléfono, relación con el alumno

4. **Área de Desarrollo del Niño**:
   - Información sobre desarrollo infantil

5. **Área Social**:
   - Datos sociales del alumno

6. **Área Comunicativa**:
   - Información sobre comunicación

7. **Área Psicológica**:
   - Datos psicológicos relevantes

8. **Área Médica**:
   - Información médica del alumno

9. **Área de Firma**:
   - Espacio para firmas y sellos oficiales

**Respuestas de Error**:

**404 - Registro No Encontrado**:
```json
{
    "success": false,
    "message": "Registro de alumno en grupo no encontrado"
}
```

**400 - Usuario No Es Alumno**:
```json
{
    "success": false,
    "message": "El usuario no es un alumno"
}
```

**500 - Error Interno**:
```json
{
    "success": false,
    "message": "Error interno del servidor al generar el PDF"
}
```

**Características del PDF**:
- **Formato**: Carta (Letter)
- **Orientación**: Vertical (Portrait)  
- **Márgenes**: 10mm en todos los lados
- **Codificación**: UTF-8
- **Vista utilizada**: `pdf.ficha-inscripcion`

**Notas Importantes**:
1. **Cambio de endpoint**: Ahora utiliza el ID de UsersGrupo para acceder a los datos del alumno a través de la relación `user`
2. **Datos académicos**: Los datos de grado, grupo, turno y período lectivo se obtienen directamente de las relaciones de UsersGrupo
3. **Fecha de inscripción**: Se toma del campo `fecha_matricula` de UsersGrupo en lugar de `created_at` del usuario
4. **Mapeo de datos**: Los campos booleanos se formatean automáticamente como 'Sí' o 'No' en el PDF
5. **Validación**: Se requiere que el usuario relacionado sea de tipo 'alumno'
6. **Relaciones**: El endpoint carga automáticamente todas las relaciones necesarias (grado, grupo, turno, período lectivo, sección)