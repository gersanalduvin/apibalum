<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Imports\StudentsImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class ImportStudentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-students {file=public/ListadoBalumBotan.xlsx}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa alumnos desde un archivo Excel a las tablas users y users_grupos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');
        
        // El storage path por defecto es storage/app/
        $fullPath = storage_path('app/' . $filePath);

        if (!file_exists($fullPath)) {
            $this->error("El archivo no existe en: {$fullPath}");
            return Command::FAILURE;
        }

        $this->info("Iniciando importación desde: {$filePath}");

        try {
            // Usar la ruta absoluta del archivo directamente
            Excel::import(new StudentsImport, $fullPath);
            $this->info("Importación completada con éxito.");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error durante la importación: " . $e->getMessage());
            \Log::error("Error en ImportStudentsCommand: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
