<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Categoria extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'inventario_categorias';

    protected $fillable = [
        'uuid',
        'codigo',
        'nombre',
        'descripcion',
        'categoria_padre_id',
        'activo',
        'created_by',
        'updated_by',
        'deleted_by',
        'is_synced',
        'synced_at',
        'updated_locally_at',
        'version'
    ];

    protected $casts = [
        'uuid' => 'string',
        'activo' => 'boolean',
        'is_synced' => 'boolean',
        'synced_at' => 'datetime',
        'updated_locally_at' => 'datetime',
        'version' => 'integer'
    ];

    protected $dates = [
        'deleted_at',
        'synced_at',
        'updated_locally_at'
    ];

    // Configuración del trait Auditable
    protected $auditableFields = ['codigo',
        'nombre',
        'descripcion',
        'categoria_padre_id',
        'activo'];
    protected $nonAuditableFields = ['updated_at', 'created_at', 'deleted_at'];
    protected $auditableEvents = ['updated'];
    protected $granularAudit = false;

    // Generar UUID automáticamente
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });

        // El cálculo de nivel y ruta_jerarquia ha sido eliminado
    }

    // Relaciones jerárquicas
    public function categoriaPadre()
    {
        return $this->belongsTo(Categoria::class, 'categoria_padre_id');
    }

    public function categoriasHijas()
    {
        return $this->hasMany(Categoria::class, 'categoria_padre_id');
    }

    public function categoriasDescendientes()
    {
        return $this->hasMany(Categoria::class, 'categoria_padre_id')->with('categoriasDescendientes');
    }

    // Relaciones con productos
    public function productos()
    {
        return $this->hasMany(Producto::class, 'categoria_id');
    }

    // Relaciones de auditoría
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    // Scopes útiles
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopeRaices($query)
    {
        return $query->whereNull('categoria_padre_id');
    }

    // Se eliminan los scopes porNivel y ordenadas porque dependen de columnas retiradas

    public function scopeNoSincronizadas($query)
    {
        return $query->where('is_synced', false);
    }

    // Métodos auxiliares
    public function esRaiz()
    {
        return is_null($this->categoria_padre_id);
    }

    public function tieneHijas()
    {
        return $this->categoriasHijas()->count() > 0;
    }

    public function getRutaCompletaAttribute()
    {
        if ($this->categoria_padre_id) {
            return $this->categoriaPadre->ruta_completa . ' > ' . $this->nombre;
        }
        return $this->nombre;
    }

    public function getJerarquiaAttribute()
    {
        $jerarquia = [];
        $categoria = $this;

        while ($categoria) {
            array_unshift($jerarquia, $categoria->nombre);
            $categoria = $categoria->categoriaPadre;
        }

        return $jerarquia;
    }

    public function obtenerTodosLosDescendientes()
    {
        $descendientes = collect();

        foreach ($this->categoriasHijas as $hija) {
            $descendientes->push($hija);
            $descendientes = $descendientes->merge($hija->obtenerTodosLosDescendientes());
        }

        return $descendientes;
    }

    public function puedeEliminar()
    {
        // No se puede eliminar si tiene productos asociados
        if ($this->productos()->count() > 0) {
            return false;
        }

        // No se puede eliminar si tiene categorías hijas
        if ($this->categoriasHijas()->count() > 0) {
            return false;
        }

        return true;
    }

    // El registro de cambios se maneja en el servicio CategoriaService
    // siguiendo el patrón estándar con estructura: accion, usuario_email, fecha, datos_anteriores, datos_nuevos

    /**
     * Obtener metadata personalizada para auditoría
     */
    public function getCustomAuditMetadata(): array
    {
        return [
            'model_name' => 'Categoria'
    ];
    }

    /**
     * @deprecated Usar getRecentAudits() del trait Auditable
     */
    public function getHistorialCambios()
    {
        return $this->cambios ?? [];
    }

}
