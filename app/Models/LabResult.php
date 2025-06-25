<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\LabTest;
use App\Models\User;
 

class LabResult extends Model
{
    protected $table = 'lab_results';

    protected $fillable = [
        'patient_id',           // NEW: Direct patient relationship
        'lab_test_id',
        'medical_record_id',
        'result_date',
        'performed_by_lab_name',
        'result_document_path',
        'structured_results',
        'interpretation',
        'reviewed_by_user_id',
        'status',
    ];

    protected $casts = [
        'result_date' => 'date',
        'structured_results' => 'array',
    ];

    /**
     * Get the patient who owns the lab result (DIRECT RELATIONSHIP).
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the lab test associated with this result.
     */
    public function labTest(): BelongsTo
    {
        return $this->belongsTo(LabTest::class);
    }

    /**
     * Get the medical record associated with this result.
     */
    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    /**
     * Get the user who reviewed the result.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    /**
     * Get the patient through direct relationship (preferred) or lab test relationship (fallback).
     */
    public function getPatientAttribute()
    {
        // Use direct patient relationship if available, fallback to lab test relationship
        return $this->patient ?? $this->labTest?->patient;
    }

    /**
     * Transform lab result for frontend display (simplified).
     */
    public function toFrontendFormat(): array
    {
              // Correction : forcer le décodage si la valeur est une chaîne
        $structuredResults = $this->structured_results;
        if (is_string($structuredResults)) {
            $decoded = json_decode($structuredResults, true);
            $structuredResults = is_array($decoded) ? $decoded : [];
        } elseif (!is_array($structuredResults)) {
            $structuredResults = [];
        }
        return [
            'id' => $this->id,
            'testName' => $this->labTest?->test_name ?? 'Unknown Test',
            'testCode' => $this->labTest?->test_code,
            'resultDate' => $this->result_date?->toDateString(),
            'status' => $this->status,
            'labName' => $this->performed_by_lab_name,
            'interpretation' => $this->interpretation,
            'reviewedBy' => $this->reviewedBy?->name,
            'results' => $this->getSimplifiedResults($structuredResults),
            'hasDocument' => !empty($this->result_document_path),
            'documentPath' => $this->result_document_path,
        ];
    }

    /**
     * Simplify complex lab results for frontend consumption.
     */
    private function getSimplifiedResults(array $results): array
    {
        $simplified = [];
        
        // Handle new structure with 'results' array
        if (isset($results['results']) && is_array($results['results'])) {
            foreach ($results['results'] as $result) {
                if (is_array($result) && isset($result['parameter'])) {
                    $simplified[] = [
                        'name' => $result['parameter'],
                        'value' => $result['value'] ?? null,
                        'unit' => $result['unit'] ?? null,
                        'referenceRange' => $result['reference_range'] ?? null,
                        'status' => $result['status'] ?? 'normal',
                    ];
                }
            }
            return $simplified;
        }
        
        // Handle legacy structure (key-value pairs)
        foreach ($results as $key => $value) {
            if (is_array($value)) {
                // Handle nested results
                if (isset($value['value']) && isset($value['unit'])) {
                    $simplified[] = [
                        'name' => ucfirst(str_replace('_', ' ', $key)),
                        'value' => $value['value'],
                        'unit' => $value['unit'],
                        'referenceRange' => $value['reference_range'] ?? null,
                        'status' => $value['status'] ?? 'normal',
                    ];
                }
            } else {
                // Handle simple key-value pairs
                $simplified[] = [
                    'name' => ucfirst(str_replace('_', ' ', $key)),
                    'value' => $value,
                    'unit' => null,
                    'referenceRange' => null,
                    'status' => 'normal',
                ];
            }
        }
        
        return $simplified;
    }

    /**
     * Scope for completed results.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for reviewed results.
     */
    public function scopeReviewed($query)
    {
        return $query->where('status', 'reviewed');
    }
}
