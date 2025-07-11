<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\BillItemResource;

class BillResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bill_number' => $this->bill_number,
            'patient' => $this->whenLoaded('patient', function () {
                return [
                    'id' => $this->patient->id,
                    'name' => $this->patient->user->name ?? 'Unknown',
                    'email' => $this->patient->user->email ?? null,
                ];
            }),
            'doctor' => $this->whenLoaded('doctor', function () {
                // Load the doctor specialty through the relationship
                $doctorSpecialty = null;
                
                // Get the specialty from the doctor model
                $doctorUser = $this->doctor;
                if ($doctorUser && $doctorUser->doctor) {
                    $doctorSpecialty = $doctorUser->doctor->specialty;
                }
                
                return [
                    'id' => $this->doctor->id,
                    'name' => $this->doctor->name,
                    'email' => $this->doctor->email,
                    'phone' => $this->doctor->phone,
                    'specialty' => $doctorSpecialty
                ];
            }),
            'amount' => (float) $this->amount,
            'issue_date' => $this->issue_date->format('Y-m-d'),
            'payment_method' => $this->payment_method,
            'description' => $this->description,
            'pdf_path' => $this->when($this->pdf_path, function () {
                return route('bills.pdf.download', ['bill' => $this->id]);
            }),
            'items' => BillItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}