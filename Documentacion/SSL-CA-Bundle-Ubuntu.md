# Configuración SSL CA Bundle para Ubuntu

## Ubicaciones Comunes de Certificados CA en Ubuntu

### 1. **Certificados del Sistema (Recomendado)**
```bash
# Verificar si existe
ls -la /etc/ssl/certs/ca-certificates.crt

# Configurar en .env
AWS_CA_BUNDLE=/etc/ssl/certs/ca-certificates.crt
```

### 2. **Directorio de Certificados Mozilla**
```bash
# Verificar directorio
ls -la /etc/ssl/certs/

# Configurar en .env (usando archivo específico)
AWS_CA_BUNDLE=/etc/ssl/certs/ca-certificates.crt
```

### 3. **Certificados de curl**
```bash
# Verificar si curl tiene certificados
curl-config --ca

# Usar la salida del comando anterior
AWS_CA_BUNDLE=/path/returned/by/curl-config
```

## Comandos para Encontrar Certificados

### **Método 1: Buscar archivos de certificados**
```bash
# Buscar archivos .crt y .pem
find /etc/ssl -name "*.crt" -o -name "*.pem" 2>/dev/null

# Buscar específicamente ca-certificates
find /etc -name "*ca-cert*" 2>/dev/null
```

### **Método 2: Verificar con openssl**
```bash
# Ver la configuración de OpenSSL
openssl version -d

# Verificar certificados
openssl ciphers -v
```

### **Método 3: Usar curl para verificar**
```bash
# Ver qué certificados usa curl
curl --version
curl-config --ca
```

## Configuración Recomendada para Ubuntu

### **En tu archivo .env de producción:**
```env
# Ubuntu/Debian - Ruta más común
AWS_CA_BUNDLE=/etc/ssl/certs/ca-certificates.crt

# O si prefieres usar el directorio completo
AWS_CA_BUNDLE=/etc/ssl/certs/
```

### **Verificar que el archivo existe:**
```bash
# Verificar archivo
test -f /etc/ssl/certs/ca-certificates.crt && echo "Existe" || echo "No existe"

# Ver información del archivo
ls -la /etc/ssl/certs/ca-certificates.crt

# Ver contenido (primeras líneas)
head -20 /etc/ssl/certs/ca-certificates.crt
```

## Configuración Alternativa si no Encuentras Certificados

### **Instalar certificados CA:**
```bash
# Ubuntu/Debian
sudo apt-get update
sudo apt-get install ca-certificates

# Actualizar certificados
sudo update-ca-certificates
```

### **Descargar certificados manualmente:**
```bash
# Crear directorio
sudo mkdir -p /etc/ssl/certs

# Descargar certificados de Mozilla
sudo wget -O /etc/ssl/certs/cacert.pem https://curl.se/ca/cacert.pem

# Usar en .env
AWS_CA_BUNDLE=/etc/ssl/certs/cacert.pem
```

## Configuración en Diferentes Archivos

### **1. En config/aws.php:**
```php
'http' => [
    'verify' => env('AWS_CA_BUNDLE', '/etc/ssl/certs/ca-certificates.crt'),
    'timeout' => 60,
    'connect_timeout' => 60,
],
```

### **2. En config/queue.php (SQS):**
```php
'sqs' => [
    // ... otras configuraciones
    'http' => [
        'verify' => env('AWS_CA_BUNDLE', '/etc/ssl/certs/ca-certificates.crt'),
        'timeout' => 60,
        'connect_timeout' => 60,
    ],
],
```

## Troubleshooting

### **Si el archivo no existe:**
```bash
# Instalar certificados
sudo apt-get install ca-certificates curl

# Verificar instalación
dpkg -l | grep ca-certificates
```

### **Permisos:**
```bash
# Verificar permisos (debe ser legible)
ls -la /etc/ssl/certs/ca-certificates.crt

# Si no tienes permisos, el usuario web debe poder leer
sudo chmod 644 /etc/ssl/certs/ca-certificates.crt
```

### **Probar conexión:**
```bash
# Probar con curl
curl -I https://sqs.us-east-2.amazonaws.com/

# Probar con PHP
php -r "echo file_get_contents('https://httpbin.org/get') ? 'OK' : 'FAIL';"
```

## Configuración por Distribución

| Distribución | Ruta Recomendada |
|--------------|------------------|
| Ubuntu/Debian | `/etc/ssl/certs/ca-certificates.crt` |
| CentOS/RHEL | `/etc/ssl/certs/ca-bundle.crt` |
| Alpine Linux | `/etc/ssl/certs/ca-certificates.crt` |
| Amazon Linux | `/etc/ssl/certs/ca-bundle.crt` |

## Notas Importantes

1. **Siempre verifica que el archivo existe** antes de configurarlo
2. **El usuario web (www-data, nginx, apache) debe poder leer el archivo**
3. **En contenedores Docker**, asegúrate de que los certificados estén disponibles
4. **Para múltiples entornos**, usa variables de entorno específicas por servidor

## Ejemplo de Script de Verificación

```bash
#!/bin/bash
# verify-ca-bundle.sh

CA_PATHS=(
    "/etc/ssl/certs/ca-certificates.crt"
    "/etc/ssl/certs/ca-bundle.crt"
    "/etc/pki/tls/certs/ca-bundle.crt"
    "/usr/share/ca-certificates/"
)

echo "Buscando certificados CA..."

for path in "${CA_PATHS[@]}"; do
    if [[ -f "$path" ]] || [[ -d "$path" ]]; then
        echo "✓ Encontrado: $path"
        ls -la "$path"
    else
        echo "✗ No encontrado: $path"
    fi
done
```