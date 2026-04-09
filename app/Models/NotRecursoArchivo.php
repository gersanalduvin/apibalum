<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class NotRecursoArchivo extends Model
{
    use HasFactory;

    protected $table = 'not_recurso_archivos';

    protected $fillable = [
        'not_recurso_id',
        'path',
        'nombre_original',
        'tipo_mime',
        'size'
    ];

    protected $appends = ['url'];

    public function recurso()
    {
        return $this->belongsTo(NotRecurso::class, 'not_recurso_id');
    }

    public function getUrlAttribute()
    {
        return $this->path ? Storage::url($this->path) : null;
    }
}
