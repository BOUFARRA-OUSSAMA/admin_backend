<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'api_identifier',
        'description',
        'is_active',
        'config',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
        'config' => 'json',
    ];

    /**
     * AI Model belongs to many users relationship
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_ai_model')
            ->withTimestamps();
    }
}
