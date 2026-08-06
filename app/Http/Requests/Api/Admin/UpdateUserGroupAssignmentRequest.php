<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserGroupAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_group_id' => ['nullable', 'integer', 'exists:tbl_user_groups,user_group_id'],
        ];
    }
}
