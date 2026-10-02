<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['started_at' => 'datetime', 'resolved_at' => 'datetime']; }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function device(): BelongsTo { return $this->belongsTo(NetworkDevice::class); }
}
