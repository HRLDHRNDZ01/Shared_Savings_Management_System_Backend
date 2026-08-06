<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:tbl_user_groups,name'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'menu_ids' => ['sometimes', 'array'],
            'menu_ids.*' => ['integer', 'exists:tbl_sidebar_menus,sidebar_menu_id'],
        ];
    }
}
