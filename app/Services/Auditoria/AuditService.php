<?php

namespace App\Services\Auditoria;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditService
{
    public function log(
        string $accion,
        ?string $entidad = null,
        mixed $entidadId = null,
        ?array $antes = null,
        ?array $despues = null,
        ?Request $request = null
    ): void {
        $req = $request ?: request();

        AuditLog::query()->create([
            'user_id' => auth()->id(),
            'accion' => $accion,
            'entidad' => $entidad,
            'entidad_id' => $entidadId !== null ? (string) $entidadId : null,
            'valores_antes' => $antes,
            'valores_despues' => $despues,
            'ip' => $req?->ip(),
            'user_agent' => $req?->userAgent(),
        ]);
    }
}

