<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PatientNote;
use App\Models\Patient;
use App\Services\Medical\TimelineEventService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PatientNoteController extends Controller
{
    use ApiResponseTrait;

    protected TimelineEventService $timelineEventService;

    public function __construct(TimelineEventService $timelineEventService)
    {
        $this->timelineEventService = $timelineEventService;
    }

    /**
     * Display a listing of notes for a patient.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Get patient ID from request or current user
            $patientId = $request->query('patient_id');
            if (!$patientId && $user->isPatient()) {
                $patient = $user->patient;
                $patientId = $patient ? $patient->id : null;
            }
            
            if (!$patientId) {
                return $this->error('Patient ID is required', 400);
            }

            $patient = Patient::findOrFail($patientId);
            
            // Check permissions
            if (!$user->isPatient() && !$user->hasPermission('patients:view-notes')) {
                return $this->error('Insufficient permissions', 403);
            }
            
            if ($user->isPatient() && $user->patient->id !== $patient->id) {
                return $this->error('Access denied', 403);
            }

            $query = PatientNote::where('patient_id', $patient->id)
                ->with(['createdBy', 'patient.personalInfo']);

            // Patients can only see public notes, doctors/staff can see all
            if ($user->isPatient()) {
                $query->where('is_private', false);
            }

            // Apply filters
            if ($request->query('note_type')) {
                $query->where('note_type', $request->query('note_type'));
            }
            
            if ($request->query('date_from')) {
                $query->whereDate('created_at', '>=', $request->query('date_from'));
            }
            
            if ($request->query('date_to')) {
                $query->whereDate('created_at', '<=', $request->query('date_to'));
            }

            $notes = $query->orderBy('created_at', 'desc')
                ->limit($request->query('limit', 50))
                ->get()
                ->map(function ($note) {
                    return $note->toFrontendFormat();
                });
            
            return $this->success($notes, 'Patient notes retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve patient notes: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created note.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'patient_id' => 'required|exists:patients,id',
                'note_type' => 'required|in:consultation,progress,discharge,general,treatment,follow_up,diagnosis',
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'is_private' => 'sometimes|boolean',
                'tags' => 'nullable|array',
                'tags.*' => 'string|max:50'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $user = Auth::user();
            
            // Check permissions
            if (!$user->hasPermission('patients:manage-notes') && !$user->isDoctor()) {
                return $this->error('Insufficient permissions', 403);
            }

            $patient = Patient::findOrFail($request->patient_id);
            
            $note = PatientNote::create([
                'patient_id' => $patient->id,
                'doctor_id' => $user->id,
                'note_type' => $request->note_type,
                'title' => $request->title,
                'content' => $request->content,
                'is_private' => $request->boolean('is_private', false)
            ]);

            // Create timeline event only if note is not private
            if (!$note->is_private) {
                $this->timelineEventService->createNoteEvent($note);
            }

            return $this->success(
                $note->toFrontendFormat(),
                'Patient note created successfully',
                201
            );
            
        } catch (\Exception $e) {
            return $this->error('Failed to create patient note: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified note.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $note = PatientNote::with(['patient.personalInfo', 'createdBy'])->findOrFail($id);
            
            // Check permissions
            if (!$user->isPatient() && !$user->hasPermission('patients:view-notes')) {
                return $this->error('Insufficient permissions', 403);
            }
            
            if ($user->isPatient() && $user->patient->id !== $note->patient_id) {
                return $this->error('Access denied', 403);
            }
            
            // Patients can only see public notes
            if ($user->isPatient() && $note->is_private) {
                return $this->error('Access denied to private note', 403);
            }

            return $this->success(
                $note->toFrontendFormat(),
                'Patient note retrieved successfully'
            );
            
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve patient note: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified note.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'sometimes|in:consultation,progress,discharge,general,treatment,follow_up',
                'title' => 'sometimes|string|max:255',
                'content' => 'sometimes|string',
                'is_private' => 'sometimes|boolean',
                'tags' => 'nullable|array',
                'tags.*' => 'string|max:50'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $user = Auth::user();
            $note = PatientNote::findOrFail($id);
            
            // Check permissions
            if (!$user->hasPermission('patients:manage-notes') && !$user->isDoctor()) {
                return $this->error('Insufficient permissions', 403);
            }
            
            // Users can only edit their own notes
            if ($note->created_by_user_id !== $user->id && !$user->hasPermission('patients:manage-all-notes')) {
                return $this->error('You can only edit your own notes', 403);
            }

            $updateData = $request->only(['type', 'title', 'content', 'is_private']);
            if ($request->has('tags')) {
                $updateData['tags'] = $request->tags ? json_encode($request->tags) : null;
            }
            
            $note->update($updateData);

            return $this->success(
                $note->fresh()->toFrontendFormat(),
                'Patient note updated successfully'
            );
            
        } catch (\Exception $e) {
            return $this->error('Failed to update patient note: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified note.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $note = PatientNote::findOrFail($id);
            
            // Check permissions
            if (!$user->hasPermission('patients:manage-notes') && !$user->isDoctor()) {
                return $this->error('Insufficient permissions', 403);
            }
            
            // Users can only delete their own notes unless they have special permission
            if ($note->created_by_user_id !== $user->id && !$user->hasPermission('patients:manage-all-notes')) {
                return $this->error('You can only delete your own notes', 403);
            }

            $note->delete();

            return $this->success(null, 'Patient note deleted successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to delete patient note: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get note types.
     */
    public function types(): JsonResponse
    {
        try {
            $types = [
                'consultation' => 'Consultation Note',
                'progress' => 'Progress Note',
                'discharge' => 'Discharge Note',
                'general' => 'General Note',
                'treatment' => 'Treatment Note',
                'follow_up' => 'Follow-up Note'
            ];

            return $this->success($types, 'Note types retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve note types: ' . $e->getMessage(), 500);
        }
    }
}
