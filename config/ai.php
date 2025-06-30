<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for the AI diagnostic service integration.
    |
    */

    'base_url' => env('AI_SERVICE_URL', 'http://localhost:8000'),
    
    'models' => [
        'melanoma' => [
            'name' => 'Melanoma Detector',
            'description' => 'Detects melanoma in skin lesion images',
            'image_type' => 'dermatological',
        ],
        'brain' => [
            'name' => 'Brain Tumor Detector',
            'description' => 'Detects tumors in brain MRI scans',
            'image_type' => 'neurological',
        ],
        'pneumonia' => [
            'name' => 'Pneumonia Detector',
            'description' => 'Detects pneumonia in chest X-rays',
            'image_type' => 'radiological',
        ],
        'breast' => [
            'name' => 'Breast Cancer Detector',
            'description' => 'Detects benign and malignant breast lesions in ultrasound images',
            'image_type' => 'ultrasound',
        ],
        'tuberculose' => [
            'name' => 'Tuberculosis Detector',
            'description' => 'Detects tuberculosis, pneumonia, and normal cases in chest X-rays',
            'image_type' => 'radiological',
        ],
    ],
    
    // LLM integration settings
    'llm' => [
        'enabled' => env('AI_LLM_ENABLED', true),
        'provider' => env('AI_LLM_PROVIDER', 'groq'),
        'model' => env('AI_LLM_MODEL', 'llama3-70b-8192'),
        'timeout' => env('AI_LLM_TIMEOUT', 45),
    ],
    
    // Image processing settings
    'image' => [
        'max_size' => env('AI_IMAGE_MAX_SIZE', 10240), // 10MB
        'allowed_types' => ['jpg', 'jpeg', 'png'],
        'storage_disk' => 'public',
        'storage_path' => 'ai_analyses',
    ],
    
    // Service settings
    'service' => [
        'timeout' => env('AI_SERVICE_TIMEOUT', 30),
        'retry_attempts' => env('AI_SERVICE_RETRY_ATTEMPTS', 1),
        'retry_delay' => env('AI_SERVICE_RETRY_DELAY', 3),
    ],
];