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
                 // $this->doctor here is the User model instance for the doctor
                return [
                    'id' => $this->doctor->id,
                    'name' => $this->doctor->name ?? 'Unknown Doctor', // Name is directly on the User model
                    // Access specialty through the User's 'doctor' profile relationship
                    'specialty' => $this->doctor->doctor ? $this->doctor->doctor->specialty : null,
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