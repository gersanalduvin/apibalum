<?php

namespace App\Services;

use App\Repositories\Contracts\AvisoRepositoryInterface;
use App\Repositories\AsignaturaGradoDocenteRepository;
use App\Repositories\UsersFamiliaRepository;
use App\Repositories\UsersGrupoRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use App\Events\AvisoCreado;
use App\Events\AvisoLeido;

class AvisoService
{
    public function __construct(
        private AvisoRepositoryInterface $avisoRepo,
        private AsignaturaGradoDocenteRepository $docenteRepo,
        private UsersFamiliaRepository $familiaRepo,
        private UsersGrupoRepository $usersGrupoRepo,
        private S3Service $s3Service
    ) {}

    public function createAviso(array $data, array $files = [])
    {
        $adjuntosMetadata = [];

        // 1. Procesar adjuntos y subir a S3
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $uploadResult = $this->s3Service->uploadFile($file, 'avisos/' . uniqid());
                if ($uploadResult['success']) {
                    $adjuntosMetadata[] = [
                        'nombre' => $file->getClientOriginalName(),
                        'key' => $uploadResult['key'],
                        'type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ];
                }
            }
        }

        // 2. Preparar links
        $links = [];
        if (isset($data['links'])) {
            $parsedLinks = is_string($data['links']) ? json_decode($data['links'], true) : $data['links'];
            if (is_array($parsedLinks)) {
                $links = $parsedLinks;
            }
        }

        $aviso = $this->avisoRepo->create(array_merge($data, [
            'adjuntos' => $adjuntosMetadata,
            'links' => $links,
            'prioridad' => $data['prioridad'] ?? 'normal',
        ]));

        // Dispatch Reverb WebSocket event to update clients in real-time
        event(new AvisoCreado($aviso));

        return $aviso;
    }

    public function updateAviso(int $id, array $data, array $files = [])
    {
        $aviso = $this->avisoRepo->findById($id);
        $adjuntosMetadata = $aviso->adjuntos ?? [];

        // 1. Procesar nuevos adjuntos y subir a S3
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $uploadResult = $this->s3Service->uploadFile($file, 'avisos/' . uniqid());
                if ($uploadResult['success']) {
                    $adjuntosMetadata[] = [
                        'nombre' => $file->getClientOriginalName(),
                        'key' => $uploadResult['key'],
                        'type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ];
                }
            }
        }

        // 2. Preparar links
        $links = $aviso->links ?? [];
        if (isset($data['links'])) {
            $parsedLinks = is_string($data['links']) ? json_decode($data['links'], true) : $data['links'];
            if (is_array($parsedLinks)) {
                $links = $parsedLinks;
            }
        }

        return $this->avisoRepo->update($id, array_merge($data, [
            'adjuntos' => $adjuntosMetadata,
            'links' => $links,
            'prioridad' => $data['prioridad'] ?? 'normal',
        ]));
    }

    public function getAvisosForUser($user, array $filters = [])
    {
        $groupIds = [];

        if ($user->tipo_usuario === 'docente') {
            $asignaciones = $this->docenteRepo->getAllByDocente($user->id);
            $groupIds = $asignaciones->pluck('grupo_id')->unique()->toArray();
        } elseif ($user->tipo_usuario === 'familia') {
            $estudiantes = $this->familiaRepo->getStudentsByFamily($user->id);
            foreach ($estudiantes as $estudiante) {
                $grupos = $this->usersGrupoRepo->findByUser($estudiante->id);
                foreach ($grupos as $g) {
                    $groupIds[] = $g->grupo_id;
                }
            }
        }

        if ($user->isSuperAdmin() || in_array($user->tipo_usuario, ['administrativo', 'superuser'])) {
            $filters['user_id'] = $user->id;
            $avisos = $this->avisoRepo->getAll($filters);
        } else {
            $avisos = $this->avisoRepo->getByGroups($groupIds, $filters);
        }

        // Marcar como leídos los avisos creados por el propio usuario
        foreach ($avisos as $aviso) {
            if ((int)$aviso->user_id === (int)$user->id) {
                $aviso->leido_por_mi = true;
            }
        }

        return $this->generatePresignedUrls($avisos);
    }

    private function generatePresignedUrls($avisos)
    {
        foreach ($avisos as $aviso) {
            if (!empty($aviso->adjuntos)) {
                $adjuntosWithUrl = [];
                foreach ($aviso->adjuntos as $adj) {
                    $adj['url'] = $this->s3Service->getPresignedUrl($adj['key']);
                    $adjuntosWithUrl[] = $adj;
                }
                $aviso->adjuntos = $adjuntosWithUrl;
            }
        }
        return $avisos;
    }

    public function markAsRead(int $avisoId, int $userId)
    {
        \Illuminate\Support\Facades\DB::table('aviso_lecturas')->insertOrIgnore([
            'aviso_id' => $avisoId,
            'user_id' => $userId,
            'read_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        event(new \App\Events\AvisoLeido($avisoId, $userId));

        return true;
    }

    public function findById(int $id)
    {
        $aviso = $this->avisoRepo->findById($id);

        if (!empty($aviso->adjuntos)) {
            $adjuntosWithUrl = [];
            foreach ($aviso->adjuntos as $adj) {
                $adj['url'] = $this->s3Service->getPresignedUrl($adj['key']);
                $adjuntosWithUrl[] = $adj;
            }
            $aviso->adjuntos = $adjuntosWithUrl;
        }

        return $aviso;
    }

    public function delete(int $id)
    {
        $aviso = $this->avisoRepo->findById($id);

        if (!empty($aviso->adjuntos)) {
            foreach ($aviso->adjuntos as $adj) {
                $this->s3Service->deleteFile($adj['key']);
            }
        }

        return $this->avisoRepo->delete($id);
    }

    public function getStatistics(int $id)
    {
        return \Illuminate\Support\Facades\DB::table('aviso_lecturas')
            ->join('users', 'aviso_lecturas.user_id', '=', 'users.id')
            ->where('aviso_lecturas.aviso_id', $id)
            ->select('users.id', 'users.primer_nombre', 'users.segundo_nombre', 'users.primer_apellido', 'users.segundo_apellido', 'aviso_lecturas.read_at')
            ->orderBy('aviso_lecturas.read_at', 'desc')
            ->get();
    }
}
