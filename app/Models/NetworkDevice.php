<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NetworkDevice extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected function casts(): array { return ['is_registered' => 'boolean', 'last_seen' => 'datetime']; }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function logs(): HasMany { return $this->hasMany(MonitoringLog::class, 'device_id'); }
    public function alerts(): HasMany { return $this->hasMany(Alert::class, 'device_id'); }
}
