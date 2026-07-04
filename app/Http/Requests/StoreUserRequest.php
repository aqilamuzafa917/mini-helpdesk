<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', new Enum(Role::class)],
            'client_id' => [
                $this->input('role') === Role::Client->value ? 'required' : 'prohibited',
                $this->input('role') === Role::Client->value ? 'exists:clients,id' : 'nullable',
            ],
            'is_active' => ['required', 'boolean'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ];
    }
}
