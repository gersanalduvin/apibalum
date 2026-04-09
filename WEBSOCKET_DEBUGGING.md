# 🐛 Guía de Debugging - WebSocket No Funciona

## ✅ Pasos para Verificar el Problema

### **1. Verificar que Reverb está corriendo**

```bash
# Debe estar corriendo en una terminal
php artisan reverb:start

# Deberías ver:
# [2026-01-01 19:38:47] Server running on http://localhost:8080
```

---

### **2. Verificar Logs del Backend**

Abre el archivo de logs de Laravel:
```bash
tail -f storage/logs/laravel.log
```

Cuando envíes un mensaje, deberías ver:
```
[2026-01-01 19:38:47] local.INFO: 🔔 MensajeEnviado Broadcasting  
{
  "mensaje_id": "019b7c31-d48a-71ee-b49d-0b2d9eec48aa",
  "current_user": 1,
  "destinatarios_count": 1,
  "channels": ["private-App.Models.User.3"]
}
```

**Si NO ves este log:**
- El evento no se está disparando
- Verifica que `MensajeEnviado::dispatch($mensaje)` se esté llamando

---

### **3. Verificar Consola del Navegador (Frontend)**

Abre las DevTools del navegador (F12) y ve a la pestaña "Console".

Deberías ver estos mensajes al cargar la página:
```
🔌 Connecting to Echo channel: App.Models.User.1
✅ Successfully subscribed to channel
```

**Si ves errores:**
```
❌ Channel error: [objeto de error]
```

Esto indica un problema de autenticación o conexión.

---

### **4. Verificar Pestaña Network (WebSocket)**

1. Abre DevTools → Pestaña "Network"
2. Filtra por "WS" (WebSocket)
3. Deberías ver una conexión activa a `ws://localhost:8080`

**Verifica:**
- ✅ Estado: "101 Switching Protocols" (conexión exitosa)
- ✅ Frames: Deberías ver mensajes entrantes y salientes

**Si la conexión falla:**
- Verifica que Reverb esté corriendo
- Verifica las variables de entorno del frontend

---

### **5. Verificar Variables de Entorno**

#### **Frontend (.env.local)**
```env
NEXT_PUBLIC_REVERB_APP_KEY=3encezqtq4ytgwlagyzz
NEXT_PUBLIC_REVERB_HOST=localhost
NEXT_PUBLIC_REVERB_PORT=8080
NEXT_PUBLIC_REVERB_SCHEME=http
NEXT_PUBLIC_API_URL=http://localhost:8081
```

#### **Backend (.env)**
```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=575383
REVERB_APP_KEY=3encezqtq4ytgwlagyzz
REVERB_APP_SECRET=1hxqxc8694impoza0lj7
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

**IMPORTANTE**: Elimina cualquier línea duplicada de `BROADCAST_CONNECTION`

---

### **6. Test Manual del Evento**

Ejecuta este comando en Tinker para probar el broadcasting:

```bash
php artisan tinker
```

```php
$mensaje = App\Models\Mensaje::latest()->first();
App\Events\MensajeEnviado::dispatch($mensaje);
```

Deberías ver el log en `storage/logs/laravel.log` y el evento en el frontend.

---

### **7. Verificar Autenticación del Canal**

El archivo `routes/channels.php` debe tener:

```php
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
```

---

## 🔧 Soluciones Comunes

### **Problema 1: Evento no se dispara**

**Síntoma**: No ves logs en `laravel.log`

**Solución**:
```php
// Verifica que esto esté en MensajeService.php
MensajeEnviado::dispatch($mensaje);
```

---

### **Problema 2: Frontend no se conecta**

**Síntoma**: No ves "✅ Successfully subscribed" en consola

**Solución**:
1. Verifica que Reverb esté corriendo
2. Verifica variables de entorno del frontend
3. Reinicia el servidor de Next.js: `pnpm dev`

---

### **Problema 3: Conexión se establece pero no recibe eventos**

**Síntoma**: Ves "✅ Successfully subscribed" pero no ves "📨 Nuevo mensaje recibido"

**Solución**:
1. Verifica que los eventos usen el punto: `.listen('.MensajeEnviado')`
2. Verifica que el nombre del evento coincida: `broadcastAs()` debe retornar `'MensajeEnviado'`

---

### **Problema 4: Error de autenticación**

**Síntoma**: Error 403 en `/broadcasting/auth`

**Solución**:
1. Verifica que el token esté en el header: `Authorization: Bearer {token}`
2. Verifica que el usuario esté autenticado
3. Verifica `routes/channels.php`

---

## 📊 Checklist de Verificación

- [ ] Reverb está corriendo (`php artisan reverb:start`)
- [ ] Variables de entorno configuradas correctamente
- [ ] No hay líneas duplicadas de `BROADCAST_CONNECTION` en `.env`
- [ ] Frontend se conecta exitosamente (ver consola)
- [ ] WebSocket aparece en Network tab
- [ ] Logs aparecen en `laravel.log` cuando envías mensaje
- [ ] Eventos usan punto: `.listen('.MensajeEnviado')`
- [ ] `broadcastAs()` retorna `'MensajeEnviado'`

---

## 🧪 Test Rápido

1. **Abre dos navegadores** (o ventanas de incógnito)
2. **Inicia sesión** con dos usuarios diferentes
3. **Usuario A** envía mensaje a **Usuario B**
4. **Verifica en consola de Usuario B**:
   - Deberías ver: `📨 Nuevo mensaje recibido (Realtime):`
5. **Verifica en `laravel.log`**:
   - Deberías ver: `🔔 MensajeEnviado Broadcasting`

---

## 📝 Comandos Útiles

```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Limpiar cache de configuración
php artisan config:clear

# Reiniciar Reverb
# Ctrl+C para detener
php artisan reverb:start

# Ver configuración de broadcasting
php artisan tinker
>>> config('broadcasting.default')
>>> config('broadcasting.connections.reverb')
```

---

## 🆘 Si Nada Funciona

1. **Detén todos los servidores**
2. **Limpia cache**:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```
3. **Reinicia en orden**:
   ```bash
   # Terminal 1
   php artisan serve --port=8081
   
   # Terminal 2
   php artisan reverb:start
   
   # Terminal 3
   pnpm dev
   ```
4. **Verifica los logs** en cada paso

---

**Fecha**: 2026-01-01
**Versión**: 1.0
