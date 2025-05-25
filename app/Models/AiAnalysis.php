<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiAnalysis extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'ai_model_id',
        'image_path',
        'condition_type',
        'results',
        'diagnosis',
        'confidence',
        'processed_at',
        'status'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'results' => 'json',
        'confidence' => 'float',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the analysis.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the AI model used for this analysis.
     */
    public function aiModel()
    {
        return $this->belongsTo(AiModel::class);
    }
}