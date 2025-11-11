<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Sanctum handles user access
    }

    public function rules(): array
    {
        return [
            'leave_type' => 'required|string|max:50',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'days'       => 'required|numeric|min:0.5',
            'reason'     => 'nullable|string|max:1000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
    }
}
