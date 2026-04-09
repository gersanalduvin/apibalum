<?php

use Illuminate\Support\Facades\Storage;

echo "🧪 Test de S3 - Versión Detallada\n\n";

try {
    // 1. Verificar driver
    echo "1️⃣ Verificando driver S3...\n";
    $disk = Storage::disk('s3');
    echo "   Driver: " . get_class($disk) . "\n";
    echo "   Adapter: " . get_class($disk->getAdapter()) . "\n\n";

    // 2. Configuración
    echo "2️⃣ Configuración:\n";
    $config = config('filesystems.disks.s3');
    echo "   Bucket: " . ($config['bucket'] ?? 'NO CONFIGURADO') . "\n";
    echo "   Region: " . ($config['region'] ?? 'NO CONFIGURADO') . "\n";
    echo "   Key: " . (isset($config['key']) && !empty($config['key']) ? 'CONFIGURADO' : 'NO CONFIGURADO') . "\n";
    echo "   Secret: " . (isset($config['secret']) && !empty($config['secret']) ? 'CONFIGURADO' : 'NO CONFIGURADO') . "\n\n";

    // 3. Test de escritura
    echo "3️⃣ Test de escritura...\n";
    $contenido = "Test " . now()->toDateTimeString();
    $nombreArchivo = 'test-' . time() . '.txt';

    try {
        // Usar putFileAs para tener más control
        $tempFile = tmpfile();
        fwrite($tempFile, $contenido);
        $tempPath = stream_get_meta_data($tempFile)['uri'];

        $path = Storage::disk('s3')->putFileAs(
            'mensajes/test',
            new \Illuminate\Http\File($tempPath),
            $nombreArchivo,
            'public'
        );

        fclose($tempFile);

        if ($path) {
            echo "   ✅ Archivo subido: $path\n";

            // Obtener URL
            $url = Storage::disk('s3')->url($path);
            echo "   📎 URL: $url\n\n";

            // 4. Test de lectura
            echo "4️⃣ Test de lectura...\n";
            if (Storage::disk('s3')->exists($path)) {
                echo "   ✅ Archivo existe\n";
                $contenidoLeido = Storage::disk('s3')->get($path);
                echo "   📖 Contenido: $contenidoLeido\n\n";

                // 5. Limpieza
                echo "5️⃣ Limpieza...\n";
                Storage::disk('s3')->delete($path);
                echo "   ✅ Archivo eliminado\n\n";

                echo "🎉 TODAS LAS PRUEBAS PASARON!\n";
            } else {
                echo "   ❌ Archivo no existe\n";
            }
        } else {
            echo "   ❌ Error: put devolvió false/null\n";
        }
    } catch (\Exception $e) {
        echo "   ❌ Error en escritura: " . $e->getMessage() . "\n";
        echo "   📍 " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
} catch (\Exception $e) {
    echo "\n❌ ERROR GENERAL: " . $e->getMessage() . "\n";
    echo "📍 " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\n📋 Stack trace:\n" . $e->getTraceAsString() . "\n";
}
