<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isDoctor();
    }

    public function rules(): array
    {
        return [
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048', // 2MB max
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'Please select an image file.',
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'The image must be a JPEG, PNG, or JPG file.',
            'image.max' => 'The image size cannot exceed 2MB.',
        ];
    }
}
