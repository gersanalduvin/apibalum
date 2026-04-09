<?php

namespace App\Services;

use App\Models\User;
use App\Models\UsersFamilia;
use Illuminate\Support\Collection;

class MensajeAuthorizationService
{
    /**
     * Obtener usuarios a los que el usuario actual puede enviar mensajes
     */
    public function getDestinatariosPermitidos(User $usuario, ?string $search = null, ?int $limit = 50): Collection
    {
        // Si es superadmin (booleano) o tiene rol administrativo/superuser
        if ($usuario->superadmin || in_array($usuario->tipo_usuario, ['administrativo', 'superuser'])) {
            return $this->getDestinatariosAdministrativo($search, $limit);
        }

        $query = match ($usuario->tipo_usuario) {
            'docente' => $this->getDestinatariosDocente($usuario, $search, $limit),
            'alumno' => $this->getDestinatariosAlumno($usuario, $search, $limit),
            'familia' => $this->getDestinatariosFamilia($usuario, $search, $limit),
            default => collect([])
        };

        return $query;
    }

    /**
     * Validar si un usuario puede enviar mensaje a una lista de destinatarios
     */
    public function puedeEnviarA(User $remitente, array $destinatariosIds): bool
    {
        // Pasar limit null para verificar contra todos los registros permitidos
        $permitidos = $this->getDestinatariosPermitidos($remitente, null, null)->pluck('id')->toArray();
        return empty(array_diff($destinatariosIds, $permitidos));
    }

    /**
     * Helper param aplicar búsqueda por nombre completo
     */
    private function applySearchScope($query, $search)
    {
        if ($search) {
            $terms = explode(' ', trim($search));

            $query->where(function ($motherQuery) use ($terms) {
                foreach ($terms as $term) {
                    $term = trim($term);
                    if ($term === '') continue;

                    $motherQuery->where(function ($q) use ($term) {
                        $q->where('primer_nombre', 'like', "%{$term}%")
                            ->orWhere('segundo_nombre', 'like', "%{$term}%")
                            ->orWhere('primer_apellido', 'like', "%{$term}%")
                            ->orWhere('segundo_apellido', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%");
                    });
                }
            });
        }
    }

    /**
     * Administrativos pueden enviar a todos (alumnos, administrativos, docentes)
     */
    private function getDestinatariosAdministrativo(?string $search = null, ?int $limit = 50): Collection
    {
        $query = User::select('id', 'tipo_usuario', 'primer_nombre', 'primer_apellido', 'primer_nombre as nombre_completo', 'email');

        // Filtros de usuarios activos según periodo lectivo
        $query->where(function ($q) {
            // Administrativos y Superusers siempre activos
            $q->whereIn('tipo_usuario', ['administrativo', 'superuser']);

            // Alumnos activos (con grupos en periodo de notas activo)
            $q->orWhere(function ($sub) {
                $sub->where('tipo_usuario', 'alumno')
                    ->whereHas('grupos.periodoLectivo', function ($p) {
                        $p->where('periodo_nota', 1);
                    });
            });

            // Docentes activos (con carga académica o guiatura en periodo activo)
            $q->orWhere(function ($sub) {
                $sub->where('tipo_usuario', 'docente')
                    ->where(function ($doc) {
                        $doc->whereHas('asignaturasDocente', function ($ad) {
                            $ad->whereHas('grupo', function ($g) {
                                $g->whereHas('periodoLectivo', function ($p) {
                                    $p->where('periodo_nota', 1);
                                });
                            });
                        })
                            ->orWhereHas('gruposComoGuia', function ($gc) {
                                $gc->whereHas('periodoLectivo', function ($p) {
                                    $p->where('periodo_nota', 1);
                                });
                            });
                    });
            });

            // Familias activas (con hijos alumnos activos)
            $q->orWhere(function ($sub) {
                $sub->where('tipo_usuario', 'familia')
                    ->whereHas('hijos', function ($hijo) {
                        $hijo->whereHas('grupos.periodoLectivo', function ($p) {
                            $p->where('periodo_nota', 1);
                        });
                    });
            });
        });

        $this->applySearchScope($query, $search);

        if ($limit) {
            $query->take($limit);
        }

        return $query->get();
    }

    /**
     * Docentes pueden enviar a: sus alumnos, familias de esos alumnos, otros docentes y administrativos
     */
    private function getDestinatariosDocente(User $docente, ?string $search = null, ?int $limit = 50): Collection
    {
        // Alumnos en grupos donde imparte clases (solo periodos activos)
        $alumnosClases = User::where('tipo_usuario', 'alumno')
            ->whereHas('grupos', function ($q) use ($docente) {
                $q->whereHas('periodoLectivo', function ($p) {
                    $p->where('periodo_nota', 1);
                })
                    ->whereHas('grupo', function ($g) use ($docente) {
                        $g->whereHas('asignaturasDocente', function ($ad) use ($docente) {
                            $ad->where('user_id', $docente->id);
                        });
                    });
            });

        // Alumnos donde es docente guía (solo periodos activos)
        $alumnosGuia = User::where('tipo_usuario', 'alumno')
            ->whereHas('grupos', function ($q) use ($docente) {
                $q->whereHas('grupo', function ($g) use ($docente) {
                    $g->where('docente_guia', $docente->id);
                })
                    ->whereHas('periodoLectivo', function ($p) {
                        $p->where('periodo_nota', 1);
                    });
            });

        // IDs Alumnos
        $idsAlumnos = $alumnosClases->pluck('id')
            ->merge($alumnosGuia->pluck('id'))
            ->unique();

        // IDs Familias de esos alumnos
        // IDs Familias de esos alumnos
        $idsFamilias = User::where('tipo_usuario', 'familia')
            ->whereHas('hijos', function ($q) use ($idsAlumnos) {
                $q->whereIn('estudiante_id', $idsAlumnos);
            })->pluck('id');

        // IDs Docentes/Admin
        $idsStaff = User::where('id', '!=', $docente->id)
            ->where(function ($q) {
                // Admin/Superuser siempre incluidos
                $q->whereIn('tipo_usuario', ['administrativo', 'superuser']);

                // Otros Docentes solo si activos
                $q->orWhere(function ($sub) {
                    $sub->where('tipo_usuario', 'docente')
                        ->where(function ($doc) {
                            $doc->whereHas('asignaturasDocente', function ($ad) {
                                $ad->whereHas('grupo', function ($g) {
                                    $g->whereHas('periodoLectivo', function ($p) {
                                        $p->where('periodo_nota', 1);
                                    });
                                });
                            })
                                ->orWhereHas('gruposComoGuia', function ($gc) {
                                    $gc->whereHas('periodoLectivo', function ($p) {
                                        $p->where('periodo_nota', 1);
                                    });
                                });
                        });
                });
            })
            ->pluck('id');

        $todosIds = $idsAlumnos->merge($idsFamilias)->merge($idsStaff)->unique();

        $query = User::whereIn('id', $todosIds);

        $this->applySearchScope($query, $search);

        if ($limit) {
            $query->take($limit);
        }

        return $query->get();
    }

    /**
     * Alumnos solo pueden enviar a sus docentes
     */
    private function getDestinatariosAlumno(User $alumno, ?string $search = null, ?int $limit = 50): Collection
    {
        // Docentes que imparten en sus grupos (solo periodo activo)
        $docentesClases = User::where('tipo_usuario', 'docente')
            ->whereHas('asignaturasDocente', function ($ad) use ($alumno) {
                $ad->whereHas('grupo', function ($g) use ($alumno) {
                    // El grupo debe pertenecer a un periodo activo
                    $g->whereHas('periodoLectivo', function ($p) {
                        $p->where('periodo_nota', 1);
                    })
                        // Y el alumno debe estar en ese grupo
                        ->whereHas('usersGrupos', function ($q) use ($alumno) {
                            $q->where('user_id', $alumno->id);
                        });
                });
            });

        // Docente guía (solo periodo activo)
        $docenteGuia = User::where('tipo_usuario', 'docente')
            ->whereHas('gruposComoGuia', function ($g) use ($alumno) {
                // El grupo debe pertenecer a un periodo activo
                $g->whereHas('periodoLectivo', function ($p) {
                    $p->where('periodo_nota', 1);
                })
                    // Y el alumno debe estar en ese grupo
                    ->whereHas('usersGrupos', function ($q) use ($alumno) {
                        $q->where('user_id', $alumno->id);
                    });
            });

        $ids = $docentesClases->pluck('id')->merge($docenteGuia->pluck('id'))->unique();

        $query = User::whereIn('id', $ids);

        $this->applySearchScope($query, $search);

        if ($limit) {
            $query->take($limit);
        }

        return $query->get();
    }

    /**
     * Familias solo pueden enviar a docentes de sus hijos
     */
    private function getDestinatariosFamilia(User $familia, ?string $search = null, ?int $limit = 50): Collection
    {
        $hijosIds = UsersFamilia::where('familia_id', $familia->id)->pluck('estudiante_id');

        if ($hijosIds->isEmpty()) {
            return collect([]);
        }

        // Docentes que imparten a sus hijos (solo periodo activo)
        $docentesClases = User::where('tipo_usuario', 'docente')
            ->whereHas('asignaturasDocente', function ($ad) use ($hijosIds) {
                $ad->whereHas('grupo', function ($g) use ($hijosIds) {
                    // El grupo debe pertenecer a un periodo activo
                    $g->whereHas('periodoLectivo', function ($p) {
                        $p->where('periodo_nota', 1);
                    })
                        // Y alguno de sus hijos debe estar en ese grupo
                        ->whereHas('usersGrupos', function ($q) use ($hijosIds) {
                            $q->whereIn('user_id', $hijosIds);
                        });
                });
            });

        // Docentes guía de sus hijos (solo periodo activo)
        $docentesGuia = User::where('tipo_usuario', 'docente')
            ->whereHas('gruposComoGuia', function ($g) use ($hijosIds) {
                // El grupo debe pertenecer a un periodo activo
                $g->whereHas('periodoLectivo', function ($p) {
                    $p->where('periodo_nota', 1);
                })
                    // Y alguno de sus hijos debe estar en ese grupo
                    ->whereHas('usersGrupos', function ($q) use ($hijosIds) {
                        $q->whereIn('user_id', $hijosIds);
                    });
            });

        $ids = $docentesClases->pluck('id')->merge($docentesGuia->pluck('id'))->unique();

        $query = User::whereIn('id', $ids);

        $this->applySearchScope($query, $search);

        if ($limit) {
            $query->take($limit);
        }

        return $query->get();
    }
}
