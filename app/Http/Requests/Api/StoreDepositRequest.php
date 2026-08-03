<?php

namespace App\Http\Requests\Api;

use App\Models\Space;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreDepositRequest extends FormRequest
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
            'space_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $userId = $this->user()?->getKey();
            $spaceId = $this->integer('space_id');

            if (! $userId || ! $spaceId) {
                return;
            }

            $allowed = Space::query()
                ->whereKey($spaceId)
                ->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                        ->orWhereHas('members', fn ($q) => $q->where('user_id', $userId));
                })
                ->exists();

            if (! $allowed) {
                $validator->errors()->add('space_id', 'The selected space id is invalid.');
            }
        });
    }
}
