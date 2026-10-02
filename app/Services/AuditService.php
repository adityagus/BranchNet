<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    public function record(string $action, ?Model $subject = null, ?array $old = null, ?array $new = null): void
    {
        AuditLog::create(['user_id' => auth()->id(), 'action' => $action, 'subject_type' => $subject ? $subject::class : null, 'subject_id' => $subject?->getKey(), 'old_values' => $old, 'new_values' => $new, 'ip_address' => request()->ip()]);
    }
}
