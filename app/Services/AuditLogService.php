<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Registra eventos sensibles en la tabla de auditoría (audit_logs).
 */
class AuditLogService
{
    public function __construct(
        private readonly AuditLog $auditLog,
        private readonly Request $request,
    ) {}

    /**
     * Registra una acción de auditoría.
     *
     * @param  array{user?: User|null, action: string, entity_type: string, entity_id?: int|null, before?: array|null, after?: array|null}  $data
     */
    public function log(array $data): void
    {
        $user = $data['user'] ?? $this->request->user();

        $this->auditLog->create([
            'user_id' => $user?->id,
            'action' => $data['action'],
            'entity_type' => $data['entity_type'],
            'entity_id' => $data['entity_id'] ?? null,
            'before' => $data['before'] ?? null,
            'after' => $data['after'] ?? null,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
        ]);
    }
}
