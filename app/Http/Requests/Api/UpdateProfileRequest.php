<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('username')) {
            $this->merge([
                'username' => Str::lower(trim((string) $this->input('username'))),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()?->getKey();

        return [
            'username' => [
                'sometimes',
                'required',
                'string',
                'alpha_dash',
                'min:3',
                'max:50',
                Rule::unique('tbl_users', 'username')->ignore($userId, 'user_id'),
            ],
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
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
