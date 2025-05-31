<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\AiAnalysis;
use App\Models\User;
use App\Repositories\Interfaces\AiAnalysisRepositoryInterface;
use App\Services\AiDiagnosticService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;

class AiDiagnosticController extends Controller
{
    use ApiResponseTrait;
    
    protected $aiDiagnosticService;
    protected $aiAnalysisRepository;
    
    public function __construct(
        AiDiagnosticService $aiDiagnosticService,
        AiAnalysisRepositoryInterface $aiAnalysisRepository
    ) {
        $this->aiDiagnosticService = $aiDiagnosticService;
        $this->aiAnalysisRepository = $aiAnalysisRepository;
    }
    
    /**
     * Get available AI models.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableModels()
    {
        try {
            $models = $this->aiDiagnosticService->getAvailableModels();
            return $this->success($models, 'Available AI models retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve available models: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Analyze a medical image using AI.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */

public function analyzeImage(Request $request)
{
    // Validate request
    $validator = Validator::make($request->all(), [
        'image' => 'required|image|mimes:jpeg,png,jpg|max:10240', // Max 10MB
        'condition_type' => 'required|string|in:melanoma,brain,pneumonia',
        'patient_id' => 'sometimes|nullable|integer|exists:users,id', // Make optional
        'patient_data' => 'sometimes|nullable|array', // Make optional and nullable
        'summary_mode' => 'sometimes|boolean'
    ]);
    
    if ($validator->fails()) {
        return $this->error('Validation failed', 422, $validator->errors());
    }
    
    try {
        $image = $request->file('image');
        $conditionType = $request->input('condition_type');
        $patientData = $request->input('patient_data', []);
        $summaryMode = $request->input('summary_mode', true);
        $patientId = $request->input('patient_id');
        
        // Get current user
        $user = JWTAuth::parseToken()->authenticate();
        
        // Add analysis metadata
        if (!is_array($patientData)) {
            $patientData = [];
        }
        
        // Add basic metadata even if patient data is empty
        $patientData['analyzed_by'] = $user->name;
        $patientData['analyzed_at'] = now()->toIso8601String();
        
        // Call the AI service
        $result = $this->aiDiagnosticService->analyzeMedicalImage(
            $conditionType,
            $image,
            $patientData,
            $summaryMode
        );
        
        // Store the image file
        $imagePath = $image->store('ai_analyses', 'public');
        
        // Find AI model
        $aiModel = AiModel::where('api_identifier', $conditionType)->first();
        
        // Only save analysis to database if we have a valid AI model
        if ($aiModel) {
            // Save analysis to database - make patient_id optional
            $analysisData = [
                'user_id' => $user->id,
                'ai_model_id' => $aiModel->id,
                'condition_type' => $conditionType,
                'image_path' => $imagePath,
                'diagnosis' => $result['ml_prediction']['predicted_class'] ?? 'Unknown',
                'confidence' => $result['ml_prediction']['confidence'] ?? 0,
                'report_data' => $result ?? [],
                'summary' => $result['llm_analysis'] ?? null,
            ];
            
            // Only add patient_id if it's provided
            if ($patientId) {
                $analysisData['patient_id'] = $patientId;
            }
            
            $analysis = $this->aiAnalysisRepository->createAnalysis($analysisData);
            
            // Add the analysis ID to the result
            $result['analysis_id'] = $analysis->id;
        }
        
        $this->logAiUsage($user->id, $conditionType, $result);
        return $this->success($result, 'Image analyzed successfully');
    } catch (\Exception $e) {
        Log::error('AI image analysis failed', [
            'error' => $e->getMessage(),
            'user_id' => Auth::id() 
        ]);
        return $this->error('Failed to analyze image: ' . $e->getMessage(), 500);
    }
}
    
    /**
     * Log AI usage activity
     */
    protected function logAiUsage($userId, $conditionType, $result)
    {
        try {
            // Create activity log
            activity()
                ->causedBy($userId)
                ->withProperties([
                    'condition_type' => $conditionType,
                    'prediction_class' => $result['ml_prediction']['predicted_class'] ?? 'Unknown',
                    'confidence' => $result['ml_prediction']['confidence'] ?? 0
                ])
                ->log('ai_analysis');
        } catch (\Exception $e) {
            Log::error('Failed to log AI usage', [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getPatientAnalyses(Request $request, User $patient)
{
    try {
        $perPage = $request->query('per_page', 15);
        $analyses = $this->aiAnalysisRepository->getPatientAnalyses($patient->id, $perPage);
        
        return $this->paginated($analyses, 'Patient AI analyses retrieved successfully');
    } catch (\Exception $e) {
        return $this->error('Failed to retrieve patient analyses: ' . $e->getMessage(), 500);
    }
}

public function getAnalysis(AiAnalysis $analysis)
{
    try {
        $analysis->load(['user', 'aiModel']);
        
        // Check permission (user should be the creator or have appropriate permission)
        $currentUser = JWTAuth::parseToken()->authenticate();
        
        if ($currentUser->id !== $analysis->user_id && 
            !$currentUser->hasPermissionTo('ai:view-all')) {
            return $this->error('You do not have permission to view this analysis', 403);
        }
        
        return $this->success($analysis);
    } catch (\Exception $e) {
        return $this->error('Failed to retrieve analysis: ' . $e->getMessage(), 500);
    }
}

}
