<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

// Test de subida de archivos a S3

echo "🧪 Probando conexión a S3...\n\n";

try {
    // 1. Verificar configuración
    echo "📋 Configuración S3:\n";
    echo "   Bucket: " . config('filesystems.disks.s3.bucket') . "\n";
    echo "   Region: " . config('filesystems.disks.s3.region') . "\n";
    echo "   URL: " . config('filesystems.disks.s3.url') . "\n\n";

    // 2. Crear un archivo de prueba
    echo "📄 Creando archivo de prueba...\n";
    $contenido = "Este es un archivo de prueba creado el " . now()->toDateTimeString();
    $nombreArchivo = 'test-' . time() . '.txt';

    // 3. Subir a S3
    echo "☁️  Subiendo a S3...\n";
    $path = Storage::disk('s3')->put('mensajes/test', $contenido);

    if ($path) {
        echo "✅ Archivo subido exitosamente!\n";
        echo "   Path: $path\n";

        // 4. Obtener URL
        $url = Storage::disk('s3')->url($path);
        echo "   URL: $url\n\n";

        // 5. Verificar que existe
        echo "🔍 Verificando existencia...\n";
        if (Storage::disk('s3')->exists($path)) {
            echo "✅ El archivo existe en S3\n\n";

            // 6. Leer contenido
            echo "📖 Leyendo contenido...\n";
            $contenidoLeido = Storage::disk('s3')->get($path);
            echo "   Contenido: $contenidoLeido\n\n";

            // 7. Eliminar archivo de prueba
            echo "🗑️  Eliminando archivo de prueba...\n";
            Storage::disk('s3')->delete($path);
            echo "✅ Archivo eliminado\n\n";

            echo "🎉 TODAS LAS PRUEBAS PASARON!\n";
            echo "✅ La funcionalidad de S3 está funcionando correctamente.\n";
        } else {
            echo "❌ El archivo NO existe en S3\n";
        }
    } else {
        echo "❌ Error al subir archivo\n";
    }
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "📍 Archivo: " . $e->getFile() . "\n";
    echo "📍 Línea: " . $e->getLine() . "\n\n";

    if ($e instanceof \Aws\S3\Exception\S3Exception) {
        echo "🔍 Detalles del error de S3:\n";
        echo "   Código: " . $e->getAwsErrorCode() . "\n";
        echo "   Mensaje: " . $e->getAwsErrorMessage() . "\n";
    }
}
