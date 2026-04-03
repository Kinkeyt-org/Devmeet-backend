<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketTier extends Model
{
    protected $fillable = ['event_id', 'name', 'price', 'capacity'];
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
