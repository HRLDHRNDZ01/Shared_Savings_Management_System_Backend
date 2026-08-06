<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SyncGroupMenusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'menu_ids' => ['present', 'array'],
            'menu_ids.*' => ['integer', 'exists:tbl_sidebar_menus,sidebar_menu_id'],
        ];
    }
}
