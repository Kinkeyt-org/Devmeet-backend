<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Event extends Model
{
protected $fillable = [
    'title',
    'description',
    'location',
    'capacity',
    'date',
    'organizer_id'
];

    public $incrementing = false;
    protected $keyType = 'string';
    protected static function booted(): void
    {
        static::creating(function ($event) {
            $event->id = (string) Str::uuid();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }
    public function tickets(): HasMany
    {
        return $this->HasMany(Ticket::class);
    }
}
