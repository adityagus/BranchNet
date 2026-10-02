<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['agent_last_seen' => 'datetime'];
    }

    public function devices(): HasMany { return $this->hasMany(NetworkDevice::class); }
    public function agents(): HasMany { return $this->hasMany(NetworkAgent::class); }
    public function alerts(): HasMany { return $this->hasMany(Alert::class); }
    public function logs(): HasMany { return $this->hasMany(MonitoringLog::class); }
}
