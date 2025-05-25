<?php

namespace App\Repositories\Eloquent;

use App\Models\AiAnalysis;
use App\Repositories\Interfaces\AiAnalysisRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class AiAnalysisRepository extends BaseRepository implements AiAnalysisRepositoryInterface
{
    /**
     * AiAnalysisRepository constructor.
     * 
     * @param AiAnalysis $model
     */
    public function __construct(AiAnalysis $model)
    {
        parent::__construct($model);
    }

    /**
     * @inheritDoc
     */
    public function getPatientAnalyses(
        int $patientId,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->model
            ->where('user_id', $patientId)
            ->with(['aiModel', 'user'])
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage);
    }

    /**
     * @inheritDoc
     */
public function createAnalysis(array $data): AiAnalysis
{
    // Ensure report_data is properly JSON encoded
    if (isset($data['report_data']) && is_array($data['report_data'])) {
        // Make sure it's not null and is properly encoded
        $data['report_data'] = empty($data['report_data']) ? '{}' : json_encode($data['report_data']);
    } else if (!isset($data['report_data']) || $data['report_data'] === null) {
        // Provide a default if missing or null
        $data['report_data'] = '{}';
    }
    return $this->model->create($data);
}

    /**
     * @inheritDoc
     */
    public function getAnalysisById(int $analysisId, array $relations = []): ?AiAnalysis
    {
        return $this->model->with($relations)->find($analysisId);
    }
}