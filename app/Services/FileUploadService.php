<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class FileUploadService
{
    /**
     * Subir foto de usuario a S3
     *
     * @param UploadedFile $file
     * @param string $userId
     * @return array
     * @throws Exception
     */
    public function uploadUserPhoto(UploadedFile $file, string $userId): array
    {
        try {
            // Validar el archivo
            $this->validateImageFile($file);

            // Generar nombre único para el archivo
            $fileName = $this->generateUniqueFileName($file, $userId);

            // Definir la ruta en S3
            $path = "{$userId}/{$fileName}";

            // Subir archivo a S3
            $uploaded = Storage::disk('s3_fotos')->put($path, file_get_contents($file), 'public');

            if (!$uploaded) {
                throw new Exception('Error al subir el archivo a S3');
            }

            // Obtener la URL pública
            $url = Storage::disk('s3_fotos')->url($path);

            return [
                'success' => true,
                'foto_url' => $url,
                'foto_path' => $path,
                'file_name' => $fileName,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ];

        } catch (Exception $e) {
            throw new Exception('Error al procesar la foto: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar foto de usuario de S3
     *
     * @param string $path
     * @return bool
     */
    public function deleteUserPhoto(string $path): bool
    {
        try {
            if (Storage::disk('s3_fotos')->exists($path)) {
                return Storage::disk('s3_fotos')->delete($path);
            }
            return true; // Si no existe, consideramos que ya está eliminado
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Validar archivo de imagen
     *
     * @param UploadedFile $file
     * @throws Exception
     */
    private function validateImageFile(UploadedFile $file): void
    {
        // Validar tamaño (máximo 5MB)
        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new Exception('El archivo es demasiado grande. Máximo 5MB permitido.');
        }

        // Validar tipo MIME
        $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new Exception('Tipo de archivo no permitido. Solo se permiten: JPG, JPEG, PNG, WEBP.');
        }

        // Validar extensión
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $allowedExtensions)) {
            throw new Exception('Extensión de archivo no permitida.');
        }
    }

    /**
     * Generar nombre único para el archivo
     *
     * @param UploadedFile $file
     * @param string $userId
     * @return string
     */
    private function generateUniqueFileName(UploadedFile $file, string $userId): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('Y-m-d_H-i-s');
        $random = Str::random(8);

        return "foto_usuario_{$userId}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Obtener información de un archivo en S3
     *
     * @param string $path
     * @return array|null
     */
    public function getFileInfo(string $path): ?array
    {
        try {
            if (!Storage::disk('s3_fotos')->exists($path)) {
                return null;
            }

            return [
                'exists' => true,
                'size' => Storage::disk('s3_fotos')->size($path),
                'last_modified' => Storage::disk('s3_fotos')->lastModified($path),
                'url' => Storage::disk('s3_fotos')->url($path)
            ];
        } catch (Exception $e) {
            return null;
        }
    }

    // Métodos de compatibilidad hacia atrás (deprecated)

    /**
     * @deprecated Usar uploadUserPhoto() en su lugar
     */
    public function uploadStudentPhoto(UploadedFile $file, string $userId): array
    {
        return $this->uploadUserPhoto($file, $userId);
    }

    /**
     * @deprecated Usar deleteUserPhoto() en su lugar
     */
    public function deleteStudentPhoto(string $path): bool
    {
        return $this->deleteUserPhoto($path);
    }
}
