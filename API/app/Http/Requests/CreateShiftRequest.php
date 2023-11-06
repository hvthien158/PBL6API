<?php

namespace App\Http\Requests;

use App\Rules\TimeRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateShiftRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|unique:shifts',
            'amount' => 'required',
            'timeValidCheckIn' => ['required', new TimeRule()],
            'timeValidCheckOut' => ['required',new TimeRule()]
        ];
    }
}