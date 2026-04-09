---
description: Guía detallada para configurar y desplegar Laravel Reverb en un entorno de producción (Linux/Nginx).
---

# Configuración de Laravel Reverb en Producción

Esta guía detalla los pasos necesarios para desplegar Laravel Reverb en un servidor de producción utilizando Nginx como proxy inverso y Supervisor para mantener el proceso activo.

## 1. Requisitos Previos

*   Servidor VPS (Ubuntu/Debian recomendado) con acceso root/sudo.
*   Proyecto Laravel instalado y funcionando.
*   Dominio configurado con certificado SSL (Let's Encrypt certbot).
*   Nginx instalado.
*   Supervisor instalado.

## 2. Configuración del Backend (Laravel)

### Variables de Entorno (.env)

En el servidor de producción, edita el archivo `.env` de tu proyecto Laravel. Es crucial usar `https` y el puerto público `443` para evitar problemas de firewall en los clientes.

```ini
# .env

# Configuración básica de Reverb
REVERB_APP_ID=tu_app_id_generado
REVERB_APP_KEY=tu_app_key_generada
REVERB_APP_SECRET=tu_app_secret_generado

# Host público (Tu dominio real)
REVERB_HOST="api.midominio.com"

# Puerto donde escucha el proceso de PHP internamente
REVERB_SERVER_PORT=8080

# Puerto público expuesto al internet (SSL estándar)
REVERB_PORT=443

# Esquema seguro
REVERB_SCHEME=https
```

### Optimización

Asegúrate de que la configuración esté cacheada en producción:
```bash
php artisan config:cache
php artisan route:cache
```

## 3. Configuración del Servidor (Nginx)

Configuraremos Nginx para actuar como un "Proxy Inverso". Recibirá las conexiones seguras en el puerto 443 y las enviará internamente a Reverb en el puerto 8080.

Edita la configuración de tu sitio en Nginx (ej. `/etc/nginx/sites-available/midominio.com`):

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name api.midominio.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name api.midominio.com;

    # Certificados SSL (Ejemplo con Let's Encrypt)
    ssl_certificate /etc/letsencrypt/live/api.midominio.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.midominio.com/privkey.pem;

    root /var/www/apicmpp/public;
    index index.php index.html index.htm;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # --- BLOQUE DE CONFIGURACIÓN REVERB ---
    location /app {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # Opcional: Si usas Laravel Pulse
    location /apps {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
    # ---------------------------------------

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock; # Verifica tu versión de PHP
    }
}
```

Valida y reinicia Nginx:
```bash
sudo nginx -t
sudo systemctl restart nginx
```

## 4. Mantener el Proceso Vivo (Supervisor)

Reverb es un servicio de larga duración. Usaremos Supervisor para que arranque automáticamente y se reinicie si falla.

Crea el archivo `/etc/supervisor/conf.d/reverb.conf`:

```ini
[program:reverb]
process_name=%(program_name)s
directory=/var/www/apicmpp
# El comando para iniciar el servidor
command=php artisan reverb:start
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/apicmpp/storage/logs/reverb.log
stopwaitsecs=3600
```

Actualiza Supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start reverb
```

## 5. Configuración del Frontend (Next.js)

En tu aplicación cliente, configura el archivo `.env.production`:

```ini
# Clave pública (Debe coincidir con REVERB_APP_KEY del backend)
NEXT_PUBLIC_REVERB_APP_KEY=tu_app_key_generada

# Tu dominio real
NEXT_PUBLIC_REVERB_HOST="api.midominio.com"

# Puerto seguro estándar (Gracias al proxy de Nginx)
NEXT_PUBLIC_REVERB_PORT=443

# Esquema seguro
NEXT_PUBLIC_REVERB_SCHEME=https
```

## 6. Verificación y Troubleshooting

1.  **Verificar puertos abiertos**: Asegúrate de que el firewall (UFW) permita tráfico en el puerto 443 (HTTPS) y bloquee el 8080 externo (solo accesible localhost).
    ```bash
    sudo ufw route allow 443
    sudo ufw deny 8080
    ```

2.  **Verificar Logs**:
    *   Logs de Laravel: `tail -f storage/logs/laravel.log`
    *   Logs de Reverb: `tail -f storage/logs/reverb.log`

3.  **Probar conexión**:
    Usa la herramienta [Pusher Debug console](https://dashboard.pusher.com/) (si usas compatibilidad) o simplemente inspecciona la red en tu navegador (F12 -> Network -> WS) y verifica que la conexión a `wss://api.midominio.com/app/...` devuelva un "Status 101 Switching Protocols".
