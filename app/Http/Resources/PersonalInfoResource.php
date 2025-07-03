<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonalInfoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->patient->user->id,
            'email' => $this->patient->user->email,
            'name' => $this->name,
            'surname' => $this->surname,
            'phone' => $this->patient->user->phone,
            'birthdate' => $this->birthdate,
            'gender' => $this->gender,
            'address' => $this->address,
            'emergency_contact' => $this->emergency_contact,
            'marital_status' => $this->marital_status,
            'blood_type' => $this->blood_type,
            'nationality' => $this->nationality,
            'profile_image' => $this->profile_image,
            'patient_id' => $this->patient_id,
            'registration_date' => $this->patient->registration_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}