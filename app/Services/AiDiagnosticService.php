<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use App\Models\AiModel;

class AiDiagnosticService
{
    protected $client;
    protected $baseUrl;
    
public function __construct()
{
    $this->client = new Client([
        'timeout' => config('ai.service.timeout', 30),
    ]);
    $this->baseUrl = config('ai.base_url', 'http://localhost:8000');
}
    
    /**
     * Analyze medical image using the AI service
     * 
     * @param string $conditionType melanoma, brain, or pneumonia
     * @param \Illuminate\Http\UploadedFile $image The image file to analyze
     * @param array $patientData Optional patient data for context
     * @param bool $summaryMode Whether to return a summarized version
     * @return array The API response
     */
public function analyzeMedicalImage($conditionType, $image, $patientData = [], $summaryMode = true)
{
    try {
        // For simpler testing, let's just send the file first without other parameters
        $response = $this->client->request('POST', "{$this->baseUrl}/predict/{$conditionType}", [
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => fopen($image->getPathname(), 'r'),
                    'filename' => $image->getClientOriginalName()
                ]
            ]
        ]);
        
        return json_decode($response->getBody()->getContents(), true);
    } catch (\Exception $e) {
        Log::error('AI Diagnostic Service Error', [
            'message' => $e->getMessage(),
            'condition_type' => $conditionType
        ]);
        
        throw $e;
    }
}
    
    /**
     * Get available AI models from the system
     * 
     * @return array
     */
    public function getAvailableModels()
    {
        return AiModel::where('is_active', true)
            ->select('id', 'name', 'api_identifier', 'description')
            ->get()
            ->toArray();
    }
}