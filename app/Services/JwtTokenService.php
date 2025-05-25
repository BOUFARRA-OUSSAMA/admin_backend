<?php

namespace App\Services;

use App\Models\JwtToken;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;

class JwtTokenService
{
    /**
     * Record a newly issued JWT token.
     *
     * @param int $userId
     * @param string $token
     * @return void
     */
    public function recordToken(int $userId, string $token): void
    {
        // Parse the token to get claims
        $payload = JWTAuth::manager()->decode(new \Tymon\JWTAuth\Token($token));
        
        // Extract token ID (jti) and expiration time
        $tokenId = $payload['jti'] ?? Str::random(16);
        $expiresAt = now()->addMinutes(config('jwt.ttl'));
        
        // Record in database
        JwtToken::create([
            'user_id' => $userId,
            'token_id' => $tokenId,
            'expires_at' => $expiresAt,
        ]);
    }
    
    /**
     * Revoke a token.
     *
     * @param string $token
     * @return void
     */
    public function revokeToken(string $token): void
    {
        try {
            $payload = JWTAuth::manager()->decode(new \Tymon\JWTAuth\Token($token));
            $tokenId = $payload['jti'] ?? null;
            
            if ($tokenId) {
                JwtToken::where('token_id', $tokenId)
                    ->update(['is_revoked' => true]);
            }
        } catch (\Exception $e) {
            // Log error but continue
            Log::error('Error revoking JWT token: ' . $e->getMessage());
        }
    }
    
    /**
     * Clean up expired tokens.
     *
     * @return int Number of deleted tokens
     */
    public function cleanupExpiredTokens(): int
    {
        return JwtToken::where('expires_at', '<', now())
            ->delete();
    }
    
    /**
     * Get count of active tokens/sessions.
     *
     * @param int $minutes
     * @return int
     */
    public function getActiveSessionsCount(int $minutes = 30): int
    {
        // Count users with active tokens from our JWT tokens table
        $fromTokensTable = JwtToken::where('expires_at', '>', now())
            ->where('is_revoked', false)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->distinct('user_id')
            ->count('user_id');
            
        return $fromTokensTable;
    }
}