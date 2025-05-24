<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JwtToken extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'token_id',
        'is_revoked',
        'expires_at'
    ];
    
    protected $casts = [
        'expires_at' => 'datetime',
        'is_revoked' => 'boolean'
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}