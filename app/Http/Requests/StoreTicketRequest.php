<?php

namespace App\Http\Requests;

use App\Enums\ClientStatus;
use App\Enums\Priority;
use App\Enums\Role;
use App\Enums\TicketStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($user->role === Role::Client) {
            return $user->client && $user->client->status === ClientStatus::Active;
        }

        return $user->role === Role::Admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = auth()->user();

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['required', new Enum(Priority::class)],
            'status' => ['nullable', new Enum(TicketStatus::class)],
            'client_id' => [
                $user && $user->role === Role::Admin ? 'required' : 'nullable',
                $user && $user->role === Role::Admin
                    ? Rule::exists('clients', 'id')->where('status', ClientStatus::Active->value)
                    : 'in:'.$user?->client_id,
            ],
            'assigned_engineer_id' => [
                $user && $user->role === Role::Admin ? 'nullable' : 'prohibited',
                $user && $user->role === Role::Admin
                    ? Rule::exists('users', 'id')->where('role', Role::Engineer->value)
                    : 'nullable',
            ],
        ];
    }
}
