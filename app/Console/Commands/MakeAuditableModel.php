<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

class MakeAuditableModel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:auditable-model {name : El nombre del modelo} {--migration : Crear también la migración}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear un modelo con el trait Auditable incluido automáticamente';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new command instance.
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $modelName = Str::studly($name);
        $tableName = Str::snake(Str::pluralStudly($modelName));

        // Crear el directorio si no existe
        $modelPath = app_path('Models');
        if (!$this->files->isDirectory($modelPath)) {
            $this->files->makeDirectory($modelPath, 0755, true);
        }

        $modelFile = $modelPath . '/' . $modelName . '.php';

        // Verificar si el modelo ya existe
        if ($this->files->exists($modelFile)) {
            $this->error("El modelo {$modelName} ya existe!");
            return 1;
        }

        // Generar el contenido del modelo
        $modelContent = $this->generateModelContent($modelName, $tableName);

        // Escribir el archivo
        $this->files->put($modelFile, $modelContent);

        $this->info("Modelo {$modelName} creado exitosamente con trait Auditable.");

        // Crear migración si se especifica la opción
        if ($this->option('migration')) {
            $this->createMigration($tableName);
        }

        return 0;
    }

    /**
     * Generate the model content.
     */
    protected function generateModelContent($modelName, $tableName)
    {
        return "<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class {$modelName} extends Model
{
    use SoftDeletes, Auditable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected \$table = '{$tableName}';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected \$fillable = [
        // Agregar aquí los campos específicos del modelo
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected \$casts = [
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected \$hidden = [
        // Agregar campos sensibles aquí si es necesario
    ];

    /**
     * Campos que no deben ser auditados automáticamente.
     * El trait Auditable excluye automáticamente: updated_at, created_at, deleted_at
     *
     * @var array<int, string>
     */
    protected \$nonAuditableFields = [
        // Agregar campos adicionales que no requieren auditoría
    ];

    /**
     * Eventos que deben ser auditados.
     * Por defecto: updated
     *
     * @var array<int, string>
     */
    protected \$auditableEvents = [
        'updated'
    ];

    // Agregar aquí las relaciones del modelo

    // Ejemplo de relación:
    // public function creator()
    // {
    //     return \$this->belongsTo(User::class, 'created_by');
    // }

    // public function updater()
    // {
    //     return \$this->belongsTo(User::class, 'updated_by');
    // }

    // public function deleter()
    // {
    //     return \$this->belongsTo(User::class, 'deleted_by');
    // }
}
";
    }

    /**
     * Create migration for the model.
     */
    protected function createMigration($tableName)
    {
        $this->call('make:migration', [
            'name' => "create_{$tableName}_table",
            '--create' => $tableName,
        ]);

        $this->info('Migración creada exitosamente. Recuerda agregar los campos de auditoría a la migración.');
        $this->line('');
        $this->line('Agrega estos campos a tu migración:');
        $this->line('$table->unsignedBigInteger(\'created_by\')->nullable();');
        $this->line('$table->unsignedBigInteger(\'updated_by\')->nullable();');
        $this->line('$table->unsignedBigInteger(\'deleted_by\')->nullable();');
        // Removido: campo cambios
        $this->line('$table->softDeletes();');
        $this->line('$table->index([\'created_by\', \'updated_by\', \'deleted_by\']);');
    }
}
