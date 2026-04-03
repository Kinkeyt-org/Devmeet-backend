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
        'banner'
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
