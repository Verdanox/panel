<?php

namespace App\Models;

use App\Contracts\Validatable;
use App\Traits\HasValidation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model implements Validatable
{
    use HasFactory;
    use HasValidation;

    public const RESOURCE_NAME = 'announcement';

    protected $fillable = [
        'title',
        'message', 
        'type',
        'is_active',
        'target_servers',
        'created_by',
        'scheduled_start',
        'scheduled_end',
    ];

    public static array $validationRules = [
        'title' => ['required', 'string', 'max:255'],
        'message' => ['required', 'string', 'max:2000'],
        'type' => ['required', 'string', 'in:info,warning,maintenance,critical'],
        'is_active' => ['boolean'],
        'target_servers' => ['nullable', 'array'],
        'target_servers.*' => ['integer', 'exists:servers,id'],
        'created_by' => ['required', 'integer', 'exists:users,id'],
        'scheduled_start' => ['nullable', 'date'],
        'scheduled_end' => ['nullable', 'date', 'after:scheduled_start'],
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'target_servers' => 'array',
            'scheduled_start' => 'datetime',
            'scheduled_end' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            'info' => 'blue',
            'warning' => 'yellow', 
            'maintenance' => 'orange',
            'critical' => 'red',
            default => 'gray',
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'info' => 'tabler-info-circle',
            'warning' => 'tabler-alert-triangle',
            'maintenance' => 'tabler-tool',
            'critical' => 'tabler-alert-octagon',
            default => 'tabler-bell',
        };
    }

    public function isScheduled(): bool
    {
        return !is_null($this->scheduled_start) || !is_null($this->scheduled_end);
    }

    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        
        if ($this->scheduled_start && $now->lt($this->scheduled_start)) {
            return false;
        }

        if ($this->scheduled_end && $now->gt($this->scheduled_end)) {
            return false;
        }

        return true;
    }

    public function targetsAllServers(): bool
    {
        return is_null($this->target_servers) || empty($this->target_servers);
    }

    public function targetsServer(int $serverId): bool
    {
        if ($this->targetsAllServers()) {
            return true;
        }

        return in_array($serverId, $this->target_servers);
    }
}
