<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Ticket extends Model
{

public $incrementing = false;
    protected $keyType = 'string';
    protected static function booted():void{
static::creating(function($event){
    $event->id = (string) Str::uuid();
});
    }
    public function user():BelongsTo{
        return $this->belongsTo(User::class, 'attendee_id');

    }
    public function event():belongsTo{
        return $this->belongsTo(Event::class);
    }
}
