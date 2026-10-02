<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkAgent extends Model
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['last_seen' => 'datetime']; }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
}
