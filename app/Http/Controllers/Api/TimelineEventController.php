<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\TimelineEvent;
use App\Services\Medical\TimelineEventService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Exception;

class TimelineEventController extends Controller
{
    protected TimelineEventService $timelineEventService;

    public function __construct(TimelineEventService $timelineEventService)
    {
        $this->timelineEventService = $timelineEventService;
    }

    /**
     * Display a listing of timeline events for a patient.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'patient_id' => 'required|exists:patients,id',
                'event_type' => 'nullable|in:appointment,prescription,vital_signs,note,file_upload,alert,manual',
                'importance' => 'nullable|in:low,medium,high',
                'limit' => 'nullable|integer|min:1|max:100',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $patientId = $request->patient_id;
            $patient = Patient::findOrFail($patientId);

            // Authorization check
            if ($user->isPatient() && $patient->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only view your own timeline'
                ], 403);
            }

            if ($user->isDoctor()) {
                // Check if doctor has relationship with patient
                $hasRelationship = $patient->appointments()
                    ->where('doctor_user_id', $user->id)
                    ->exists();
                    
                if (!$hasRelationship) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You can only view timeline for your patients'
                    ], 403);
                }
            }

            $query = TimelineEvent::where('patient_id', $patientId)
                ->with(['patient.personalInfo']);

            // For patients, filter to only visible events
            if ($user->isPatient()) {
                $query->where('is_visible_to_patient', true);
            }

            // Apply filters
            if ($request->event_type) {
                $query->where('event_type', $request->event_type);
            }

            if ($request->importance) {
                $query->where('importance', $request->importance);
            }

            if ($request->date_from) {
                $query->whereDate('event_date', '>=', $request->date_from);
            }

            if ($request->date_to) {
                $query->whereDate('event_date', '<=', $request->date_to);
            }

            // Order by importance and date
            $query->orderByRaw("CASE 
                WHEN importance = 'high' THEN 1 
                WHEN importance = 'medium' THEN 2 
                WHEN importance = 'low' THEN 3 
                ELSE 4 
            END")->orderBy('event_date', 'desc');

            $limit = $request->get('limit', 20);
            $events = $query->limit($limit)->get();

            $transformedEvents = $events->map(function($event) use ($user) {
                return $event->toFrontendFormat($user->isPatient());
            });

            return response()->json([
                'success' => true,
                'data' => $transformedEvents,
                'pagination' => [
                    'limit' => $limit,
                    'total' => $events->count(),
                ],
                'message' => 'Timeline events retrieved successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve timeline events',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified timeline event.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $event = TimelineEvent::with(['patient.personalInfo'])->findOrFail($id);
            $patient = $event->patient;

            // Authorization check
            if ($user->isPatient() && $patient->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only view your own timeline events'
                ], 403);
            }

            if ($user->isDoctor()) {
                // Check if doctor has relationship with patient
                $hasRelationship = $patient->appointments()
                    ->where('doctor_user_id', $user->id)
                    ->exists();
                    
                if (!$hasRelationship) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You can only view timeline events for your patients'
                    ], 403);
                }
            }

            // Check visibility for patients
            if ($user->isPatient() && !$event->is_visible_to_patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'This timeline event is not visible to patients'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $event->toFrontendFormat($user->isPatient()),
                'message' => 'Timeline event retrieved successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve timeline event',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get timeline summary for a patient.
     */
    public function summary(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'patient_id' => 'required|exists:patients,id',
                'days' => 'nullable|integer|min:1|max:365',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $patientId = $request->patient_id;
            $patient = Patient::findOrFail($patientId);
            $days = $request->get('days', 30);

            // Authorization check
            if ($user->isPatient() && $patient->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only view your own timeline summary'
                ], 403);
            }

            if ($user->isDoctor()) {
                // Check if doctor has relationship with patient
                $hasRelationship = $patient->appointments()
                    ->where('doctor_user_id', $user->id)
                    ->exists();
                    
                if (!$hasRelationship) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You can only view timeline summary for your patients'
                    ], 403);
                }
            }

            $query = TimelineEvent::where('patient_id', $patientId)
                ->where('event_date', '>=', now()->subDays($days));

            // For patients, filter to only visible events
            if ($user->isPatient()) {
                $query->where('is_visible_to_patient', true);
            }

            // Get summary statistics
            $totalEvents = $query->count();
            $eventsByType = $query->groupBy('event_type')
                ->selectRaw('event_type, count(*) as count')
                ->pluck('count', 'event_type')
                ->toArray();

            $eventsByImportance = $query->groupBy('importance')
                ->selectRaw('importance, count(*) as count')
                ->pluck('count', 'importance')
                ->toArray();

            $recentEvents = $query->orderBy('event_date', 'desc')
                ->limit(5)
                ->get()
                ->map(function($event) use ($user) {
                    return $event->toFrontendFormat($user->isPatient());
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'period_days' => $days,
                    'total_events' => $totalEvents,
                    'events_by_type' => $eventsByType,
                    'events_by_importance' => $eventsByImportance,
                    'recent_events' => $recentEvents,
                ],
                'message' => 'Timeline summary retrieved successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve timeline summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
