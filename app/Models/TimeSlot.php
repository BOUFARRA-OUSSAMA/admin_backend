<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimeSlot extends Model
{
    use HasFactory;
    protected $table = 'time_slots';
    protected $fillable = [
        'start_time',
        'end_time',
        'is_active',
    ];

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}