<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FixCambiosComments extends Command
{
    protected $signature = 'fix:cambios-comments';
    protected $description = 'Corrige los comentarios duplicados del campo cambios en todos los modelos';

    public function handle()
    {
        $this->info('🔧 Iniciando corrección de comentarios duplicados...');

        $modelsPath = app_path('Models');
        $files = File::files($modelsPath);
        $fixedFiles = 0;

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $content = File::get($file->getPathname());
                $originalContent = $content;

                // Corregir en fillable
                $content = preg_replace(
                    "/'cambios' \/\/ Mantener para compatibilidad temporal \/\/ Mantener para compatibilidad temporal,/",
                    "'cambios', // Mantener para compatibilidad temporal",
                    $content
                );

                // Corregir en fillable sin coma
                $content = preg_replace(
                    "/'cambios' \/\/ Mantener para compatibilidad temporal \/\/ Mantener para compatibilidad temporal$/m",
                    "'cambios', // Mantener para compatibilidad temporal",
                    $content
                );

                // Corregir en casts
                $content = preg_replace(
                    "/'cambios' \/\/ Mantener para compatibilidad temporal \/\/ Mantener para compatibilidad temporal => 'array',/",
                    "'cambios' => 'array', // Mantener para compatibilidad temporal",
                    $content
                );

                // Corregir en arrays de exclusión
                $content = preg_replace(
                    "/'cambios' \/\/ Mantener para compatibilidad temporal \/\/ Mantener para compatibilidad temporal\]/",
                    "'cambios']",
                    $content
                );

                // Corregir unset statements
                $content = preg_replace(
                    "/unset\(\$datosNuevos\['cambios' \/\/ Mantener para compatibilidad temporal \/\/ Mantener para compatibilidad temporal'\]\);/",
                    "unset(\$datosNuevos['cambios']);",
                    $content
                );

                if ($content !== $originalContent) {
                    File::put($file->getPathname(), $content);
                    $this->info("   ✅ Corregido: " . $file->getFilename());
                    $fixedFiles++;
                }
            }
        }

        $this->info("🎉 Corrección completada. Archivos corregidos: {$fixedFiles}");
        return 0;
    }
}
