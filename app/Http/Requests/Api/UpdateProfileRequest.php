<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()?->getKey();

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('tbl_users', 'email')->ignore($userId, 'user_id'),
            ],
            'contact_number' => ['sometimes', 'nullable', 'string', 'max:30'],
            'current_password' => ['required_with:password', 'string'],
            'password' => ['sometimes', 'required', 'confirmed', Password::defaults()],
        ];
    }
}
