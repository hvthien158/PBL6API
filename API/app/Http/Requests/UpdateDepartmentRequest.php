<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\NameDepartmentUpdateRule;
class UpdateDepartmentRequest extends FormRequest
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
            'departmentName' => ['required', 'string', new NameDepartmentUpdateRule],
            'address'=> 'required|string',
            'email' =>'required|email',
            'phoneNumber' => 'required|string'
        ];
    }
}
