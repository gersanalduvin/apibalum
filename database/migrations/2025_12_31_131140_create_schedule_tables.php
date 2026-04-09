<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Limpiar estado anterior en caso de fallo parcial
        Schema::dropIfExists('horario_clases');
        Schema::dropIfExists('docente_disponibilidad');
        Schema::dropIfExists('config_bloques_horarios');
        Schema::dropIfExists('config_aulas');

        // 1. Configuración de Aulas / Laboratorios / Canchas
        Schema::create('config_aulas', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('nombre');
            $table->enum('tipo', ['aula', 'laboratorio', 'cancha', 'otro'])->default('aula');
            $table->integer('capacidad')->default(30);
            $table->boolean('activa')->default(true);

            // Campos de auditoría standard
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->boolean('is_synced')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('updated_locally_at')->nullable();
            $table->integer('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });

        // 2. Bloques de Horario (Franjas por Turno)
        Schema::create('config_bloques_horarios', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('turno_id')->constrained('config_turnos')->cascadeOnDelete();
            $table->string('nombre'); // "Bloque 1", "Recreo"
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->boolean('es_recreo')->default(false);
            $table->integer('orden');
            $table->json('dias_aplicables')->nullable(); // Ej: [1,2,3,4,5] (Lunes a Viernes)

            // Campos de auditoría standard
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->boolean('is_synced')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('updated_locally_at')->nullable();
            $table->integer('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });

        // 3. Disponibilidad de Docentes (Bloqueos o Preferencias)
        Schema::create('docente_disponibilidad', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // ID único para sincronización
            $table->foreignId('docente_id')->constrained('users')->cascadeOnDelete();
            $table->integer('dia_semana'); // 1=Lunes, 7=Domingo
            // Puede ser por bloque entero...
            $table->foreignId('bloque_horario_id')->nullable()->constrained('config_bloques_horarios')->cascadeOnDelete();
            // ...o por rango de horas personalizado (si bloque_horario_id es null)
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();

            $table->boolean('disponible')->default(false); // False = No disponible (Bloqueado)
            $table->string('motivo')->nullable();

            // Campos de auditoría standard
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->boolean('is_synced')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('updated_locally_at')->nullable();
            $table->integer('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });

        // 4. Horario Clases (El resultado final generado)
        Schema::create('horario_clases', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('periodo_lectivo_id')->constrained('conf_periodo_lectivos')->cascadeOnDelete();

            $table->integer('dia_semana'); // 1=Lunes
            $table->foreignId('bloque_horario_id')->constrained('config_bloques_horarios')->cascadeOnDelete();

            // Relaciones principales
            $table->foreignId('grupo_id')->constrained('config_grupos')->cascadeOnDelete();
            $table->foreignId('asignatura_grado_id')->nullable()->constrained('not_asignatura_grado')->nullOnDelete();
            $table->string('titulo_personalizado')->nullable(); // Para "Práctica de campo" sin asignatura

            $table->foreignId('docente_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('aula_id')->nullable()->constrained('config_aulas')->nullOnDelete();

            // Flags de control
            $table->boolean('is_fijo')->default(false); // Si es true, el algoritmo no lo mueve
            $table->boolean('es_simultanea')->default(false); // Si es true, permite solapamiento con otras del mismo grupo

            // Overrides de hora (para "Jugar con la franja")
            $table->time('hora_inicio_real')->nullable();
            $table->time('hora_fin_real')->nullable();

            // Campos de auditoría standard
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->boolean('is_synced')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('updated_locally_at')->nullable();
            $table->integer('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horario_clases');
        Schema::dropIfExists('docente_disponibilidad');
        Schema::dropIfExists('config_bloques_horarios');
        Schema::dropIfExists('config_aulas');
    }
};
