<?php

namespace App\Http\Requests\Api;

use App\Enums\SpaceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSpaceRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', Rule::enum(SpaceType::class)],
            'target_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
