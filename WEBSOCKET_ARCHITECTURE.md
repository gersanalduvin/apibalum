# 🔌 Arquitectura de WebSockets en el Sistema de Mensajería

## 📊 Diagrama de Flujo Completo

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         ARQUITECTURA WEBSOCKETS                          │
└─────────────────────────────────────────────────────────────────────────┘

┌──────────────────┐         ┌──────────────────┐         ┌──────────────────┐
│   FRONTEND       │         │   LARAVEL API    │         │  LARAVEL REVERB  │
│   (Next.js)      │         │   (Backend)      │         │   (WebSocket)    │
└──────────────────┘         └──────────────────┘         └──────────────────┘
        │                            │                            │
        │ 1. Conexión WebSocket      │                            │
        │───────────────────────────────────────────────────────>│
        │    createEcho()            │                            │
        │    + Bearer Token          │                            │
        │                            │                            │
        │                            │ 2. Validar Token           │
        │                            │<───────────────────────────│
        │                            │    /broadcasting/auth      │
        │                            │                            │
        │ 3. Suscripción a Canal     │                            │
        │───────────────────────────────────────────────────────>│
        │    App.Models.User.{id}    │                            │
        │                            │                            │
        │                            │ 4. Verificar Autorización  │
        │                            │<───────────────────────────│
        │                            │    routes/channels.php     │
        │                            │                            │
        │ 5. Conexión Establecida ✅ │                            │
        │<───────────────────────────────────────────────────────│
        │                            │                            │
        │                            │                            │
        │                  ┌─────────────────────┐               │
        │                  │  USUARIO ENVÍA       │               │
        │                  │  MENSAJE/RESPUESTA   │               │
        │                  └─────────────────────┘               │
        │                            │                            │
        │ 6. POST /api/v1/mensajes   │                            │
        │───────────────────────────>│                            │
        │                            │                            │
        │                            │ 7. Crear Mensaje           │
        │                            │    MensajeService          │
        │                            │                            │
        │                            │ 8. Dispatch Event          │
        │                            │    MensajeEnviado::dispatch│
        │                            │                            │
        │                            │ 9. Broadcast a Canales     │
        │                            │───────────────────────────>│
        │                            │    broadcastOn()           │
        │                            │    - App.Models.User.3     │
        │                            │    - App.Models.User.5     │
        │                            │                            │
        │                            │                            │
        │ 10. Recibir Evento         │                            │
        │<───────────────────────────────────────────────────────│
        │     .MensajeEnviado        │                            │
        │     { mensaje: {...} }     │                            │
        │                            │                            │
        │ 11. Actualizar UI          │                            │
        │     - Refrescar lista      │                            │
        │     - Actualizar contadores│                            │
        │     - Mostrar notificación │                            │
        │                            │                            │
```

---

## 🔧 Componentes del Sistema

### **1. Laravel Reverb (Servidor WebSocket)**

**Ubicación**: `php artisan reverb:start`

**Función**: 
- Servidor WebSocket que maneja conexiones en tiempo real
- Escucha en `ws://localhost:8080` (desarrollo)
- Gestiona canales privados y públicos
- Autentica usuarios mediante tokens

**Configuración**: `config/broadcasting.php`
```php
'reverb' => [
    'driver' => 'reverb',
    'key' => env('REVERB_APP_KEY'),
    'secret' => env('REVERB_APP_SECRET'),
    'app_id' => env('REVERB_APP_ID'),
]
```

---

### **2. Backend - Eventos de Broadcasting**

#### **A. Evento `MensajeEnviado`**

**Ubicación**: `app/Events/MensajeEnviado.php`

**Función**: Se dispara cuando se crea un mensaje o respuesta

```php
class MensajeEnviado implements ShouldBroadcastNow
{
    public function __construct(public Mensaje $mensaje) {}

    // Define a qué canales enviar
    public function broadcastOn(): array
    {
        $currentUserId = auth()->id();
        
        // Obtener destinatarios de la tabla relacional
        $userIds = MensajeDestinatario::where('mensaje_id', $this->mensaje->id)
            ->where('user_id', '!=', $currentUserId)
            ->pluck('user_id')
            ->unique();

        // Agregar remitente si no es el usuario actual
        if ($this->mensaje->remitente_id !== $currentUserId) {
            $userIds->push($this->mensaje->remitente_id);
        }

        // Crear canales privados para cada usuario
        return $userIds
            ->unique()
            ->map(fn($userId) => new PrivateChannel('App.Models.User.' . $userId))
            ->values()
            ->toArray();
    }

    // Nombre del evento en el frontend
    public function broadcastAs(): string
    {
        return 'MensajeEnviado';
    }

    // Datos que se envían
    public function broadcastWith(): array
    {
        return [
            'mensaje' => $this->mensaje->loadMissing('remitente'),
            'tipo' => 'mensaje'
        ];
    }
}
```

#### **B. Evento `MensajeLeido`**

**Ubicación**: `app/Events/MensajeLeido.php`

**Función**: Se dispara cuando un usuario marca un mensaje como leído

```php
class MensajeLeido implements ShouldBroadcastNow
{
    public function broadcastOn(): array
    {
        // Solo notificar al remitente
        return [
            new PrivateChannel('App.Models.User.' . $this->mensaje->remitente_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'MensajeLeido';
    }
}
```

---

### **3. Backend - Autorización de Canales**

**Ubicación**: `routes/channels.php`

```php
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
```

**Función**:
- Verifica que el usuario solo pueda escuchar su propio canal
- Se ejecuta cuando el frontend intenta suscribirse a un canal privado

---

### **4. Frontend - Configuración de Laravel Echo**

#### **A. Configuración del Cliente**

**Ubicación**: `src/lib/echo.ts`

```typescript
export const createEcho = (token: string) => {
  return new Echo({
    broadcaster: 'reverb',
    key: process.env.NEXT_PUBLIC_REVERB_APP_KEY,
    wsHost: process.env.NEXT_PUBLIC_REVERB_HOST,
    wsPort: process.env.NEXT_PUBLIC_REVERB_PORT ?? 8080,
    wssPort: process.env.NEXT_PUBLIC_REVERB_PORT ?? 8080,
    forceTLS: (process.env.NEXT_PUBLIC_REVERB_SCHEME ?? 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: `${process.env.NEXT_PUBLIC_API_URL}/broadcasting/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    },
  });
};
```

**Función**:
- Crea instancia de Laravel Echo
- Configura conexión con Reverb
- Incluye token de autenticación

---

#### **B. Escuchar Eventos en Componentes**

**Ubicación**: `src/features/mensajeria/components/MensajeriaPage.tsx`

```typescript
useEffect(() => {
  if (!user || !accessToken) return;

  const echo = createEcho(accessToken);

  // Suscribirse al canal privado del usuario
  const channel = echo.private(`App.Models.User.${user.id}`);

  // Escuchar evento MensajeEnviado
  channel.listen('.MensajeEnviado', (event: any) => {
    console.log('Nuevo mensaje recibido:', event);
    
    // Refrescar lista de mensajes
    setRefreshKey(prev => prev + 1);
    
    // Actualizar contadores
    fetchContadores();
  });

  // Escuchar evento MensajeLeido
  channel.listen('.MensajeLeido', (event: any) => {
    console.log('Mensaje leído:', event);
    
    // Actualizar contadores
    fetchContadores();
  });

  // Cleanup al desmontar
  return () => {
    channel.stopListening('.MensajeEnviado');
    channel.stopListening('.MensajeLeido');
    echo.disconnect();
  };
}, [user, accessToken]);
```

---

## 🔄 Flujo de Datos Completo

### **Escenario: Gersan envía mensaje a Ilich**

```
1. GERSAN (Frontend)
   ↓
   POST /api/v1/mensajes
   { destinatarios: [3], asunto: "Hola", contenido: "..." }
   ↓

2. BACKEND (Laravel)
   ↓
   MensajeController::store()
   ↓
   MensajeService::crearMensaje()
   ↓
   - Crear registro en tabla 'mensajes'
   - Crear registros en 'mensaje_destinatarios' (user_id: 3)
   ↓
   MensajeEnviado::dispatch($mensaje)
   ↓

3. EVENTO (Broadcasting)
   ↓
   broadcastOn() determina canales:
   - App.Models.User.3 (Ilich)
   ↓
   broadcastWith() prepara datos:
   { mensaje: {...}, tipo: 'mensaje' }
   ↓

4. REVERB (WebSocket Server)
   ↓
   Envía evento a canal: App.Models.User.3
   ↓

5. ILICH (Frontend - Conectado al canal)
   ↓
   channel.listen('.MensajeEnviado', (event) => {
     // Actualizar UI
     setRefreshKey(prev => prev + 1);
     fetchContadores();
   })
   ↓
   UI se actualiza automáticamente ✅
```

---

## 🎯 Ventajas de esta Arquitectura

### **1. Tiempo Real**
✅ Los mensajes aparecen instantáneamente sin recargar
✅ Los contadores se actualizan en vivo
✅ Las notificaciones son inmediatas

### **2. Escalabilidad**
✅ Cada usuario tiene su propio canal privado
✅ Solo recibe eventos relevantes para él
✅ No hay polling innecesario

### **3. Seguridad**
✅ Canales privados con autenticación
✅ Token Bearer en cada conexión
✅ Verificación de autorización en `channels.php`

### **4. Eficiencia**
✅ Una sola conexión WebSocket por usuario
✅ Eventos específicos por canal
✅ Datos mínimos transmitidos

---

## 🔍 Debugging

### **Ver Conexiones Activas**
```bash
# En el terminal de Reverb verás:
[2026-01-01 19:35:50] Connection established: socket_id_123
[2026-01-01 19:35:51] Subscribed to: private-App.Models.User.1
```

### **Logs en Frontend**
```javascript
// En la consola del navegador:
console.log('Nuevo mensaje recibido:', event);
// { mensaje: { id: '...', asunto: '...', ... }, tipo: 'mensaje' }
```

### **Verificar Evento en Backend**
```php
// En MensajeService.php
Log::info('Dispatching MensajeEnviado', [
    'mensaje_id' => $mensaje->id,
    'destinatarios' => $destinatariosIds
]);
```

---

## 📝 Variables de Entorno

### **Backend (.env)**
```env
BROADCAST_DRIVER=reverb
REVERB_APP_ID=123456
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

### **Frontend (.env.local)**
```env
NEXT_PUBLIC_REVERB_APP_KEY=your-app-key
NEXT_PUBLIC_REVERB_HOST=localhost
NEXT_PUBLIC_REVERB_PORT=8080
NEXT_PUBLIC_REVERB_SCHEME=http
NEXT_PUBLIC_API_URL=http://localhost:8081
```

---

## 🚀 Comandos para Ejecutar

```bash
# Backend - Iniciar servidor API
php artisan serve --port=8081

# Backend - Iniciar WebSocket server
php artisan reverb:start

# Frontend - Iniciar Next.js
pnpm dev
```

---

## ✅ Resumen

**Laravel Reverb** actúa como el servidor WebSocket que mantiene conexiones persistentes con los clientes.

**Laravel Echo** (frontend) se conecta a Reverb y escucha eventos en canales específicos.

**Eventos de Broadcasting** (backend) determinan qué usuarios reciben qué notificaciones.

**Canales Privados** aseguran que cada usuario solo reciba sus propios mensajes.

**Flujo bidireccional** permite comunicación en tiempo real sin polling.

---

**¡Así funciona toda la arquitectura de WebSockets en el sistema de mensajería!** 🎉
