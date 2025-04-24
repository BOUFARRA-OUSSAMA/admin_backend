<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class PatientController extends Controller
{
    /**
     * Display a listing of patients.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Get the patient role
        $patientRole = Role::where('code', 'patient')->first();

        if (!$patientRole) {
            return response()->json([
                'success' => false,
                'message' => 'Patient role not found'
            ], 404);
        }

        // Get all users with patient role
        $patients = $patientRole->users()->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Patients retrieved successfully',
            'data' => [
                'items' => $patients->items(),
                'pagination' => [
                    'total' => $patients->total(),
                    'current_page' => $patients->currentPage(),
                    'per_page' => $patients->perPage(),
                    'last_page' => $patients->lastPage()
                ]
            ]
        ]);
    }

    /**
     * Store a newly created patient in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Create the user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'status' => 'active', // Patients are active by default
        ]);

        // Get the patient role
        $patientRole = Role::where('code', 'patient')->first();

        if (!$patientRole) {
            return response()->json([
                'success' => false,
                'message' => 'Patient role not defined in the system'
            ], 500);
        }

        // Assign patient role
        $user->roles()->attach($patientRole->id);

        // Track activity
        activity_log(
            $request->user() ? $request->user()->id : null,
            'create',
            'Patient',
            'Created new patient account',
            User::class,
            $user->id,
            $request->ip()
        );

        return response()->json([
            'success' => true,
            'message' => 'Patient created successfully',
            'data' => $user->load('roles')
        ], 201);
    }

    /**
     * Display the specified patient.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $user = User::findOrFail($id);

        // Check if user is a patient
        if (!$user->isPatient()) {
            return response()->json([
                'success' => false,
                'message' => 'The specified user is not a patient'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $user->load('roles')
        ]);
    }
}
