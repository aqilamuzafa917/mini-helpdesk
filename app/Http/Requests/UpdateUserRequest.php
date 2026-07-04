<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === Role::Admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->route('user');
        $userId = $user instanceof User ? $user->id : $user;

        if (empty($userId)) {
            $userId = $this->input('id') ?? $this->input('user_id');
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email,'.$userId,
            ],
            'role' => ['required', new Enum(Role::class)],
            'client_id' => [
                $this->input('role') === Role::Client->value ? 'required' : 'prohibited',
                $this->input('role') === Role::Client->value ? 'exists:clients,id' : 'nullable',
            ],
            'is_active' => ['required', 'boolean'],
            'password' => ['nullable', 'string', Password::defaults(), 'confirmed'],
        ];
    }
}
