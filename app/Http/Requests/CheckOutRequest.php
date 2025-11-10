<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckOutRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // location string optional
            'check_out_location' => 'nullable|string|max:255',
            // file is required (photo mandatory). Accept common image types, max 5MB
            'check_out_photo' => 'required|file|image|mimes:jpeg,png,jpg|max:5120',
        ];
    }

    public function messages()
    {
        return [
            'check_out_photo.required' => 'Photo is required for check-out.',
            'check_out_photo.image' => 'Photo must be an image file.',
        ];
    }
}
