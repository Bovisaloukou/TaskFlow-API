<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMemberRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('members.update-role');
    }

    public function rules(): array
    {
        return [
            'role' => ['required', 'in:admin,manager,user'],
        ];
    }
}
