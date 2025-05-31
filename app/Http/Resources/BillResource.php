<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
                return [
                    'id' => $this->doctor->id,
                    'name' => $this->doctor->name ?? 'Unknown',
                    'specialty' => $this->doctor->doctor_profile->specialty ?? null,
                ];
            }),
            'amount' => (float) $this->amount,
            'issue_date' => $this->issue_date->format('Y-m-d'),
            'due_date' => $this->due_date ? $this->due_date->format('Y-m-d') : null,
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