<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\User;
use App\Models\Role;
use App\Models\PersonalInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReceptionistPatientController extends Controller
{
    /**
     * Display a listing of patients for receptionist
     */
    public function index(Request $request)
    {
        try {
            // Récupérer les patients avec pagination
            $query = Patient::with('user', 'personalInfo');

            // Filtres
            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('user', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            if ($request->has('status')) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('status', $request->status);
                });
            }

            $patients = $query->orderBy('created_at', 'desc')->paginate(15);

            // Transformer les données pour correspondre à votre format frontend
            $formattedPatients = $patients->map(function ($patient) {
                return [
                    'id' => $patient->id,
                    'name' => $patient->user->name,
                    'email' => $patient->user->email,
                    'phone' => $patient->user->phone,
                    'dob' => $patient->personalInfo->birth_date ?? null,
                    'profile_image' => $patient->user->profile_image,
                    'status' => $patient->user->status,
                    'nationality' => $patient->personalInfo->nationality ?? null,
                    'blood_type' => $patient->personalInfo->blood_type ?? null,
                    'marital_status' => $patient->personalInfo->marital_status ?? null,
                    'gender' => $patient->personalInfo->gender ?? null,
                    'address' => $patient->personalInfo->address ?? null,
                ];
            });

            return response()->json([
                'data' => $formattedPatients,
                'pagination' => [
                    'total' => $patients->total(),
                    'per_page' => $patients->perPage(),
                    'current_page' => $patients->currentPage(),
                    'last_page' => $patients->lastPage(),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to retrieve patients: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created patient
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'phone' => 'nullable|string|max:20',
                'dob' => 'nullable|date',
                'nationality' => 'nullable|string|max:100',
                'blood_type' => 'nullable|string|max:10',
                'marital_status' => 'nullable|string|max:50',
                'gender' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:255',
                'status' => 'nullable|string|in:active,inactive,pending',
                'photo' => 'nullable|image|max:2048', // max 2MB
            ]);

            // Gérer l'upload de photo
            $profileImage = null;
            if ($request->hasFile('photo')) {
                $file = $request->file('photo');
                $path = $file->store('patients', 'public');
                $profileImage = '/storage/' . $path;
            }

            // Créer un utilisateur avec le rôle patient
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => bcrypt('password'), // Mot de passe par défaut, à changer
                'status' => $validated['status'] ?? 'active',
                'profile_image' => $profileImage ?? '/assets/images/default-user.png',
            ]);

            // Attribuer le rôle de patient
            $patientRole = Role::where('code', 'patient')->first();
            if ($patientRole) {
                $user->roles()->attach($patientRole->id);
            }

            // Créer l'enregistrement patient
            $patient = Patient::create([
                'user_id' => $user->id,
                'registration_date' => now(),
            ]);

            // Créer les informations personnelles
            $personalInfo = new PersonalInfo([
                'birth_date' => $validated['dob'] ?? null,
                'nationality' => $validated['nationality'] ?? null,
                'blood_type' => $validated['blood_type'] ?? null,
                'marital_status' => $validated['marital_status'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'address' => $validated['address'] ?? null,
            ]);
            
            $patient->personalInfo()->save($personalInfo);

            return response()->json([
                'id' => $patient->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'dob' => $validated['dob'] ?? null,
                'profile_image' => $user->profile_image,
                'status' => $user->status,
                'nationality' => $validated['nationality'] ?? null,
                'blood_type' => $validated['blood_type'] ?? null,
                'marital_status' => $validated['marital_status'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'address' => $validated['address'] ?? null,
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create patient: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified patient
     */
    public function show($id)
    {
        try {
            $patient = Patient::with('user', 'personalInfo')->findOrFail($id);

            return response()->json([
                'id' => $patient->id,
                'name' => $patient->user->name,
                'email' => $patient->user->email,
                'phone' => $patient->user->phone,
                'dob' => $patient->personalInfo->birth_date ?? null,
                'profile_image' => $patient->user->profile_image,
                'status' => $patient->user->status,
                'nationality' => $patient->personalInfo->nationality ?? null,
                'blood_type' => $patient->personalInfo->blood_type ?? null,
                'marital_status' => $patient->personalInfo->marital_status ?? null,
                'gender' => $patient->personalInfo->gender ?? null,
                'address' => $patient->personalInfo->address ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to retrieve patient: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified patient
     */
    public function update(Request $request, $id)
    {
        try {
            $patient = Patient::with('user', 'personalInfo')->findOrFail($id);
            $user = $patient->user;

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
                'phone' => 'nullable|string|max:20',
                'dob' => 'nullable|date',
                'nationality' => 'nullable|string|max:100',
                'blood_type' => 'nullable|string|max:10',
                'marital_status' => 'nullable|string|max:50',
                'gender' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:255',
                'status' => 'nullable|string|in:active,inactive,pending',
                'photo' => 'nullable|image|max:2048', // max 2MB
            ]);

            // Gérer l'upload de photo
            if ($request->hasFile('photo')) {
                $file = $request->file('photo');
                $path = $file->store('patients', 'public');
                $user->profile_image = '/storage/' . $path;
            }

            // Mettre à jour l'utilisateur
            if (isset($validated['name'])) $user->name = $validated['name'];
            if (isset($validated['email'])) $user->email = $validated['email'];
            if (isset($validated['phone'])) $user->phone = $validated['phone'];
            if (isset($validated['status'])) $user->status = $validated['status'];
            $user->save();

            // Mettre à jour ou créer les infos personnelles
            $personalInfo = $patient->personalInfo;
            if (!$personalInfo) {
                $personalInfo = new PersonalInfo();
            }

            // Mettre à jour les informations personnelles
            if (isset($validated['dob'])) $personalInfo->birth_date = $validated['dob'];
            if (isset($validated['nationality'])) $personalInfo->nationality = $validated['nationality'];
            if (isset($validated['blood_type'])) $personalInfo->blood_type = $validated['blood_type'];
            if (isset($validated['marital_status'])) $personalInfo->marital_status = $validated['marital_status'];
            if (isset($validated['gender'])) $personalInfo->gender = $validated['gender'];
            if (isset($validated['address'])) $personalInfo->address = $validated['address'];
            $patient->personalInfo()->save($personalInfo);

            return response()->json([
                'id' => $patient->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'dob' => $personalInfo->birth_date,
                'profile_image' => $user->profile_image,
                'status' => $user->status,
                'nationality' => $personalInfo->nationality,
                'blood_type' => $personalInfo->blood_type,
                'marital_status' => $personalInfo->marital_status,
                'gender' => $personalInfo->gender,
                'address' => $personalInfo->address,
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update patient: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified patient
     */
    public function destroy($id)
    {
        try {
            $patient = Patient::with('user')->findOrFail($id);
            
            // Supprimer l'utilisateur (et par cascade, le patient)
            $patient->user->delete();
            
            return response()->json(['message' => 'Patient deleted successfully'], 200);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete patient: ' . $e->getMessage()], 500);
        }
    }
}