<?php

namespace App\Traits;

use App\Models\Audit;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait Auditable
{
    /**
     * Boot del trait
     */
    protected static function bootAuditable()
    {
        static::created(function ($model) {
            $model->auditEvent('created');
        });

        static::updated(function ($model) {
            $model->auditEvent('updated');
        });

        static::deleted(function ($model) {
            $model->auditEvent('deleted');
        });
    }

    /**
     * Relación polimórfica con auditorías
     */
    public function audits(): MorphMany
    {
        return $this->morphMany(Audit::class, 'model');
    }

    /**
     * Crear registro de auditoría
     */
    protected function auditEvent(string $event)
    {
        // Verificar si el modelo debe ser auditado
        if (!$this->shouldAudit($event)) {
            return;
        }

        $user = Auth::user();
        $changes = $this->getAuditableChanges($event);

        // Si no hay cambios significativos, no crear auditoría
        if (empty($changes) && $event === 'updated') {
            return;
        }

        // Crear auditoría granular por campo (si está habilitada)
        if ($this->shouldCreateGranularAudit()) {
            $this->createGranularAudits($event, $changes, $user);
        }

        // Crear auditoría de registro completo
        $this->createCompleteAudit($event, $changes, $user);
    }

    /**
     * Crear auditorías granulares por campo
     */
    protected function createGranularAudits(string $event, array $changes, $user)
    {
        foreach ($changes as $field => $values) {
            Audit::create([
                'user_id' => $user ? $user->id : null,
                'model_type' => get_class($this),
                'model_id' => $this->getKey(),
                'event' => $event,
                'table_name' => $this->getTable(),
                'column_name' => $field,
                'old_value' => $values['old'] ?? null,
                'new_value' => $values['new'] ?? null,
                'ip' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'metadata' => $this->getAuditMetadata(),
            ]);
        }
    }

    /**
     * Crear auditoría de registro completo
     */
    protected function createCompleteAudit(string $event, array $changes, $user)
    {
        $oldValues = [];
        $newValues = [];

        foreach ($changes as $field => $values) {
            $oldValues[$field] = $values['old'] ?? null;
            $newValues[$field] = $values['new'] ?? null;
        }

        // Para eventos de creación, solo guardar los valores nuevos
        if ($event === 'created') {
            $oldValues = null;
            $newValues = $this->getAttributes();
        }

        // Para eventos de eliminación, solo guardar los valores anteriores
        if ($event === 'deleted') {
            $oldValues = $this->getOriginal();
            $newValues = null;
        }

        Audit::create([
            'user_id' => $user ? $user->id : null,
            'model_type' => get_class($this),
            'model_id' => $this->getKey(),
            'event' => $event,
            'table_name' => $this->getTable(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'metadata' => $this->getAuditMetadata(),
        ]);
    }

    /**
     * Obtener cambios auditables
     */
    protected function getAuditableChanges(string $event): array
    {
        if ($event === 'created' || $event === 'deleted') {
            return [];
        }

        $changes = [];
        $auditableFields = $this->getAuditableFields();

        foreach ($this->getDirty() as $field => $newValue) {
            // Solo auditar campos permitidos
            if (!empty($auditableFields) && !in_array($field, $auditableFields)) {
                continue;
            }

            // Excluir campos no auditables
            if (in_array($field, $this->getNonAuditableFields())) {
                continue;
            }

            $changes[$field] = [
                'old' => $this->getOriginal($field),
                'new' => $newValue,
            ];
        }

        return $changes;
    }

    /**
     * Verificar si el modelo debe ser auditado
     */
    protected function shouldAudit(string $event): bool
    {
        // Verificar si el evento está habilitado
        $auditableEvents = $this->getAuditableEvents();
        if (!empty($auditableEvents) && !in_array($event, $auditableEvents)) {
            return false;
        }

        // Verificar si hay un usuario autenticado (opcional)
        if ($this->requiresAuthenticatedUser() && !Auth::check()) {
            return false;
        }

        return true;
    }

    /**
     * Verificar si debe crear auditoría granular
     */
    protected function shouldCreateGranularAudit(): bool
    {
        return property_exists($this, 'granularAudit') ? $this->granularAudit : false;
    }

    /**
     * Obtener campos auditables
     */
    protected function getAuditableFields(): array
    {
        return property_exists($this, 'auditableFields') ? $this->auditableFields : [];
    }

    /**
     * Obtener campos no auditables
     */
    protected function getNonAuditableFields(): array
    {
        $default = ['updated_at', 'created_at', 'deleted_at', 'created_by', 'updated_by', 'deleted_by'];
        $custom = property_exists($this, 'nonAuditableFields') ? $this->nonAuditableFields : [];

        return array_merge($default, $custom);
    }

    /**
     * Obtener eventos auditables
     */
    protected function getAuditableEvents(): array
    {
        return property_exists($this, 'auditableEvents') ? $this->auditableEvents : ['updated'];
    }

    /**
     * Verificar si requiere usuario autenticado
     */
    protected function requiresAuthenticatedUser(): bool
    {
        return property_exists($this, 'requiresAuthenticatedUser') ? $this->requiresAuthenticatedUser : false;
    }

    /**
     * Obtener metadatos adicionales para la auditoría
     */
    protected function getAuditMetadata(): array
    {
        $metadata = [
            'url' => Request::fullUrl(),
            'method' => Request::method(),
        ];

        // Agregar metadatos personalizados si existen
        if (method_exists($this, 'getCustomAuditMetadata')) {
            $metadata = array_merge($metadata, $this->getCustomAuditMetadata());
        }

        return $metadata;
    }

    /**
     * Obtener auditorías recientes
     */
    public function getRecentAudits(int $limit = 10)
    {
        return $this->audits()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener auditorías por evento
     */
    public function getAuditsByEvent(string $event)
    {
        return $this->audits()
            ->where('event', $event)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtener historial de cambios de un campo específico
     */
    public function getFieldHistory(string $field)
    {
        return $this->audits()
            ->where('column_name', $field)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
