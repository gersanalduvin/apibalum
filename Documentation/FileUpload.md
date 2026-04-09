# API de Subida de Archivos

Esta documentación describe los endpoints disponibles para la subida y gestión de archivos en el sistema, específicamente para fotos de usuarios.

## Tabla de Contenidos

- [Configuración](#configuración)
- [Endpoints Disponibles](#endpoints-disponibles)
- [Subir Foto de Usuario](#subir-foto-de-usuario)
- [Eliminar Foto de Usuario](#eliminar-foto-de-usuario)
- [Validaciones](#validaciones)
- [Respuestas](#respuestas)
- [Ejemplos de Uso](#ejemplos-de-uso)
- [Permisos Requeridos](#permisos-requeridos)

## Configuración

El sistema utiliza **Amazon S3** para el almacenamiento de archivos. La configuración se encuentra en el archivo `.env`:

```env
# AWS S3 Configuration
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=your_region
AWS_BUCKET=your_bucket_name
AWS_USE_PATH_STYLE_ENDPOINT=false
```

## Endpoints Disponibles

### Rutas por Tipo de Usuario

| Tipo de Usuario | Ruta de Subida | Ruta de Eliminación | Permisos |
|----------------|----------------|---------------------|----------|
| **Usuarios Generales** | `POST /api/v1/users/{id}/upload-photo` | `DELETE /api/v1/users/{id}/delete-photo` | `usuarios.editar` |
| **Alumnos** | `POST /api/v1/usuarios/alumnos/{id}/upload-photo` | `DELETE /api/v1/usuarios/alumnos/{id}/delete-photo` | `usuarios.alumnos.subir_foto` / `usuarios.alumnos.eliminar_foto` |
| **Docentes** | `POST /api/v1/usuarios-docentes/{id}/upload-photo` | `DELETE /api/v1/usuarios-docentes/{id}/delete-photo` | Sin middleware específico |
| **Administrativos** | `POST /api/v1/usuarios-administrativos/{id}/upload-photo` | `DELETE /api/v1/usuarios-administrativos/{id}/delete-photo` | Sin middleware específico |
| **Familias** | `POST /api/v1/usuarios-familias/{id}/upload-photo` | `DELETE /api/v1/usuarios-familias/{id}/delete-photo` | Sin middleware específico |

---

## Subir Foto de Usuario

### Endpoint
```
POST /api/v1/{tipo-usuario}/{id}/upload-photo
```

### Parámetros de Ruta
- `id` (integer, requerido): ID del usuario al que se le subirá la foto

### Parámetros del Cuerpo (multipart/form-data)
- `foto_file` (file, requerido): Archivo de imagen a subir

### Validaciones
- **Tipo de archivo**: Debe ser una imagen
- **Formatos permitidos**: JPEG, JPG, PNG, WEBP
- **Tamaño máximo**: 5MB (5120 KB)
- **Campo requerido**: El archivo es obligatorio

### Headers Requeridos
```
Content-Type: multipart/form-data
Authorization: Bearer {token}
```

### Respuesta Exitosa (200 OK)
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "email": "usuario@ejemplo.com",
            "foto_url": "https://bucket.s3.region.amazonaws.com/1/foto_1234567890.jpg",
            "foto_path": "1/foto_1234567890.jpg",
            "foto_uploaded_at": "2024-01-15T10:30:00.000000Z",
            // ... otros campos del usuario
        },
        "foto_info": {
            "url": "https://bucket.s3.region.amazonaws.com/1/foto_1234567890.jpg",
            "file_name": "foto_1234567890.jpg",
            "file_size": 1048576,
            "mime_type": "image/jpeg"
        }
    },
    "message": "Foto subida exitosamente"
}
```

### Respuestas de Error

#### Usuario no encontrado (404 Not Found)
```json
{
    "success": false,
    "message": "Usuario no encontrado",
    "errors": []
}
```

#### Validación fallida (422 Unprocessable Entity)
```json
{
    "success": false,
    "message": "Validation errors",
    "errors": {
        "foto_file": [
            "La foto es obligatoria"
        ]
    }
}
```

#### Error de servidor (500 Internal Server Error)
```json
{
    "success": false,
    "message": "Error al procesar la foto: [detalle del error]",
    "errors": []
}
```

---

## Eliminar Foto de Usuario

### Endpoint
```
DELETE /api/v1/{tipo-usuario}/{id}/delete-photo
```

### Parámetros de Ruta
- `id` (integer, requerido): ID del usuario al que se le eliminará la foto

### Headers Requeridos
```
Authorization: Bearer {token}
```

### Respuesta Exitosa (200 OK)
```json
{
    "success": true,
    "data": {
        "id": 1,
        "email": "usuario@ejemplo.com",
        "foto_url": null,
        "foto_path": null,
        "foto_uploaded_at": null,
        // ... otros campos del usuario
    },
    "message": "Foto eliminada exitosamente"
}
```

### Respuestas de Error

#### Usuario no encontrado (404 Not Found)
```json
{
    "success": false,
    "message": "Usuario no encontrado",
    "errors": []
}
```

#### Usuario sin foto (400 Bad Request)
```json
{
    "success": false,
    "message": "El usuario no tiene foto para eliminar",
    "errors": []
}
```

#### Error al eliminar (500 Internal Server Error)
```json
{
    "success": false,
    "message": "Error al eliminar la foto",
    "errors": []
}
```

---

## Validaciones

### Archivo de Imagen
- **Campo**: `foto_file`
- **Tipo**: `file|image`
- **Formatos**: `jpeg,jpg,png,webp`
- **Tamaño máximo**: `5120` KB (5MB)
- **Requerido**: Sí

### Mensajes de Validación
```json
{
    "foto_file.required": "La foto es obligatoria",
    "foto_file.file": "El archivo de foto debe ser un archivo válido",
    "foto_file.image": "El archivo debe ser una imagen",
    "foto_file.mimes": "La foto debe ser de tipo: JPEG, JPG, PNG o WEBP",
    "foto_file.max": "La foto no puede ser mayor a 5MB"
}
```

---

## Ejemplos de Uso

### cURL - Subir Foto
```bash
curl -X POST \
  'https://api.ejemplo.com/api/v1/users/1/upload-photo' \
  -H 'Authorization: Bearer tu_token_aqui' \
  -H 'Content-Type: multipart/form-data' \
  -F 'foto_file=@/ruta/a/tu/imagen.jpg'
```

### cURL - Eliminar Foto
```bash
curl -X DELETE \
  'https://api.ejemplo.com/api/v1/users/1/delete-photo' \
  -H 'Authorization: Bearer tu_token_aqui'
```

### JavaScript (Fetch API) - Subir Foto
```javascript
const uploadPhoto = async (userId, file) => {
    const formData = new FormData();
    formData.append('foto_file', file);
    
    try {
        const response = await fetch(`/api/v1/users/${userId}/upload-photo`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
            },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('Foto subida exitosamente:', result.data);
            return result.data;
        } else {
            console.error('Error:', result.message);
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Error al subir foto:', error);
        throw error;
    }
};

// Uso
const fileInput = document.getElementById('foto_file');
const file = fileInput.files[0];
uploadPhoto(1, file);
```

### JavaScript (Fetch API) - Eliminar Foto
```javascript
const deletePhoto = async (userId) => {
    try {
        const response = await fetch(`/api/v1/users/${userId}/delete-photo`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('Foto eliminada exitosamente:', result.data);
            return result.data;
        } else {
            console.error('Error:', result.message);
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Error al eliminar foto:', error);
        throw error;
    }
};

// Uso
deletePhoto(1);
```

### HTML Form - Subir Foto
```html
<form id="uploadForm" enctype="multipart/form-data">
    <div>
        <label for="foto_file">Seleccionar foto:</label>
        <input type="file" id="foto_file" name="foto_file" accept="image/jpeg,image/jpg,image/png,image/webp" required>
    </div>
    <button type="submit">Subir Foto</button>
</form>

<script>
document.getElementById('uploadForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const userId = 1; // ID del usuario
    
    try {
        const response = await fetch(`/api/v1/users/${userId}/upload-photo`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Foto subida exitosamente');
            // Actualizar la interfaz con la nueva foto
            document.getElementById('userPhoto').src = result.data.foto_info.url;
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Error al subir la foto: ' + error.message);
    }
});
</script>
```

---

## Permisos Requeridos

### Por Tipo de Usuario

| Endpoint | Permiso Requerido | Descripción |
|----------|-------------------|-------------|
| `POST /api/v1/users/{id}/upload-photo` | `usuarios.editar` | Permite subir fotos a usuarios generales |
| `DELETE /api/v1/users/{id}/delete-photo` | `usuarios.editar` | Permite eliminar fotos de usuarios generales |
| `POST /api/v1/usuarios-alumnos/{id}/upload-photo` | `usuarios.alumnos.subir_foto` | Permite subir fotos a alumnos |
| `DELETE /api/v1/usuarios-alumnos/{id}/delete-photo` | `usuarios.alumnos.eliminar_foto` | Permite eliminar fotos de alumnos |

**Nota**: Los endpoints para docentes, administrativos y familias no tienen middleware de permisos específicos configurados actualmente.

---

## Notas Técnicas

### Almacenamiento
- Los archivos se almacenan en **Amazon S3**
- La estructura de carpetas es: `{user_id}/{filename}`
- Los nombres de archivo se generan de forma única para evitar conflictos
- Los archivos se almacenan con visibilidad pública

### Comportamiento del Sistema
1. **Subida de foto**: Si el usuario ya tiene una foto, la anterior se elimina automáticamente antes de subir la nueva
2. **Eliminación de foto**: Se elimina tanto el archivo de S3 como las referencias en la base de datos
3. **Validación**: Se valida tanto el tipo de archivo como el tamaño antes de procesar
4. **Transacciones**: Las operaciones de base de datos están protegidas con transacciones

### Campos de Base de Datos Actualizados
- `foto_url`: URL pública del archivo en S3
- `foto_path`: Ruta del archivo en S3 (para eliminación)
- `foto_uploaded_at`: Timestamp de cuando se subió la foto

---

## Solución de Problemas

### Error "Missing boundary in multipart/form-data"
- **Causa**: El campo del archivo no coincide con el esperado por el backend
- **Solución**: Asegúrate de usar `foto_file` como nombre del campo

### Error "La foto es obligatoria"
- **Causa**: No se está enviando el archivo o el nombre del campo es incorrecto
- **Solución**: Verifica que el campo se llame `foto_file` y contenga un archivo válido

### Error de tamaño de archivo
- **Causa**: El archivo excede los 5MB permitidos
- **Solución**: Redimensiona o comprime la imagen antes de subirla

### Error de formato no soportado
- **Causa**: El archivo no es JPEG, JPG, PNG o WEBP
- **Solución**: Convierte la imagen a uno de los formatos soportados
