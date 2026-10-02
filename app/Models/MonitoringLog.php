<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringLog extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];
    protected function casts(): array { return ['checked_at' => 'datetime']; }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function device(): BelongsTo { return $this->belongsTo(NetworkDevice::class); }
}
