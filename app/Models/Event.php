<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'location',
        'capacity',
        'date',
        'organizer_id',
        'banner',
        'is_free',
        'price',
    ];
    protected $casts = [
        'date'    => 'date', // Turns the string into a Carbon date object
        'is_free' => 'boolean',// Ensures this is always true/false, not 1/0
        'price'   => 'decimal:2',// Ensures price always has 2 decimal places
    ];
    public $incrementing = false;
    protected $keyType = 'string';
    protected static function booted(): void
    {
        static::creating(function ($event) {
            $event->id = (string) Str::uuid();
        });
    }
    /**
     * Accessor: Automatically provides the full S3 URL for the banner.
     */
    protected function banner(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? Storage::disk('s3')->url($value) : null,
        );
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }
    public function tickets(): HasMany
    {
        return $this->HasMany(Ticket::class);
    }
    public function ticketTiers(): HasMany
    {
        return $this->HasMany(TicketTier::class);
    }


    public function likedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_likes')->withTimestamps();
    }

    public function savedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_saves')->withTimestamps();
    }
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }
}
