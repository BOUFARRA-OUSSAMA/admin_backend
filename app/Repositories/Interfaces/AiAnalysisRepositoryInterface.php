<?php

namespace App\Repositories\Interfaces;

use App\Models\AiAnalysis;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface AiAnalysisRepositoryInterface extends EloquentRepositoryInterface
{
    /**
     * Get analyses for a specific patient.
     * 
     * @param int $patientId
     * @param string $sortBy
     * @param string $sortDirection
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPatientAnalyses(
        int $patientId,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator;

    /**
     * Create a new AI analysis.
     * 
     * @param array $data
     * @return AiAnalysis
     */
    public function createAnalysis(array $data): AiAnalysis;

    /**
     * Get analysis by ID.
     * 
     * @param int $analysisId
     * @param array $relations
     * @return AiAnalysis|null
     */
    public function getAnalysisById(int $analysisId, array $relations = []): ?AiAnalysis;
}