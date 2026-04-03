<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Event;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public $incrementing = false;

    // 2. Tell Laravel the ID is a string (UUID)
    protected $keyType = 'string';

    // 3. Automatically generate a UUID when a new user is created
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'profile_picture',
    ];
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'organizer_id');
    }
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
    public function likedEvents(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_likes')->withTimestamps();
    }

    public function savedEvents(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_saves')->withTimestamps();
    }

    protected function profilePicture(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!$value) return null;

                // If the string already starts with http, it's a full URL, return it.
                if (str_starts_with($value, 'http')) return $value;

            // Otherwise, ask the S3 disk to generate the full public URL
                /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
                $disk = Storage::disk('s3');
                return $disk->url($value);
            },
        );
    }
}
