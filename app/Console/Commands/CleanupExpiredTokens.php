<?php

namespace App\Console\Commands;

use App\Services\JwtTokenService;
use Illuminate\Console\Command;

class CleanupExpiredTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jwt:cleanup-tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired JWT tokens';

    /**
     * Execute the console command.
     *
     * @param JwtTokenService $jwtTokenService
     * @return int
     */
    public function handle(JwtTokenService $jwtTokenService)
    {
        $count = $jwtTokenService->cleanupExpiredTokens();
        $this->info("Cleaned up {$count} expired tokens.");
        
        return Command::SUCCESS;
    }
}