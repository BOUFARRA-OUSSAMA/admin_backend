<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Models\Role;
use App\Repositories\Interfaces\PatientRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class PatientRepository implements PatientRepositoryInterface
{
    /**
     * @var User
     */
    protected $model;

    /**
     * PatientRepository constructor.
     * 
     * @param User $model
     */
    public function __construct(User $model)
    {
        $this->model = $model;
    }

    /**
     * @inheritDoc
     */
    public function getFilteredPatients(
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        // Get the patient role
        $patientRole = Role::where('code', 'patient')->first();

        if (!$patientRole) {
            // Return empty paginator if patient role doesn't exist
            return $this->model->newQuery()->where('id', 0)->paginate($perPage);
        }

        // Query patients by role
        $query = $patientRole->users();

        // Apply filters
        if (isset($filters['search']) && $filters['search']) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%")
                    ->orWhere('users.phone', 'like', "%{$search}%");
            });
        }

        if (isset($filters['status']) && $filters['status']) {
            $query->where('users.status', $filters['status']);
        }

        // Apply sorting
        $query->orderBy('users.' . $sortBy, $sortDirection);

        // Load relationships
        $query->with('roles');

        return $query->paginate($perPage);
    }

    /**
     * @inheritDoc
     */
    public function createPatient(array $data): User
    {
        // Create new user
        $patient = $this->model->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
            'status' => $data['status'] ?? 'active', // Default to active for patients
        ]);

        // Attach patient role
        $patientRole = Role::where('code', 'patient')->first();
        if ($patientRole) {
            $patient->roles()->attach($patientRole->id);
        }

        return $patient->load('roles');
    }

    /**
     * @inheritDoc
     */
    public function getPatientById(int $patientId, array $relations = []): User
    {
        $query = $this->model->newQuery();

        if (count($relations) > 0) {
            $query->with($relations);
        }

        $patient = $query->findOrFail($patientId);

        // Verify this is actually a patient
        if (!$patient->isPatient()) {
            throw new \Exception('User is not a patient');
        }

        return $patient;
    }

    /**
     * @inheritDoc
     */
    public function updatePatient(int $patientId, array $data): bool
    {
        $patient = $this->getPatientById($patientId);

        // Update user fields
        if (isset($data['name'])) {
            $patient->name = $data['name'];
        }

        if (isset($data['email'])) {
            $patient->email = $data['email'];
        }

        if (isset($data['password'])) {
            $patient->password = Hash::make($data['password']);
        }

        if (isset($data['phone'])) {
            $patient->phone = $data['phone'];
        }

        if (isset($data['status'])) {
            $patient->status = $data['status'];
        }

        return $patient->save();
    }

    /**
     * @inheritDoc
     */
    public function deletePatient(int $patientId): bool
    {
        $patient = $this->getPatientById($patientId);

        // Detach all roles before deletion
        $patient->roles()->detach();

        return $patient->delete();
    }
}
