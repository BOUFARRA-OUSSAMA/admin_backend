<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AiModel;

class AiModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $models = [
            [
                'name' => 'Melanoma Detector',
                'api_identifier' => 'melanoma',
                'description' => 'AI model for detecting melanoma in skin lesion images',
                'is_active' => true,
                'config' => json_encode([
                    'image_size' => [224, 224],
                    'model_version' => '1.0',
                    'image_type' => 'dermatological'
                ])
            ],
            [
                'name' => 'Brain Tumor Detector',
                'api_identifier' => 'brain',
                'description' => 'AI model for detecting tumors in brain MRI scans',
                'is_active' => true,
                'config' => json_encode([
                    'image_size' => [224, 224],
                    'model_version' => '1.0',
                    'image_type' => 'neurological'
                ])
            ],
            [
                'name' => 'Pneumonia Detector',
                'api_identifier' => 'pneumonia',
                'description' => 'AI model for detecting pneumonia in chest X-rays',
                'is_active' => true,
                'config' => json_encode([
                    'image_size' => [224, 224],
                    'model_version' => '1.0',
                    'image_type' => 'radiological'
                ])
            ]
        ];

        foreach ($models as $modelData) {
            AiModel::updateOrCreate(
                ['api_identifier' => $modelData['api_identifier']],
                $modelData
            );
        }
    }
}