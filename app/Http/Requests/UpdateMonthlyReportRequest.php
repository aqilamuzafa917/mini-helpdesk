<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMonthlyReportRequest extends FormRequest
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

        // Only Admin and Client roles can access reports. Engineer cannot.
        if ($user->role !== Role::Admin && $user->role !== Role::Client) {
            return false;
        }

        // Client can only request report for their own client company
        if ($user->role === Role::Client && $this->has('client_id') && (int) $this->input('client_id') !== (int) $user->client_id) {
            return false;
        }

        // Only Admin can submit or save remarks
        if ($this->has('remarks') && $user->role !== Role::Admin) {
            return false;
        }

        return true;
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
            'client_id' => [
                $user && $user->role === Role::Admin ? 'required' : 'nullable',
                $user && $user->role === Role::Admin ? 'exists:clients,id' : 'in:'.$user?->client_id,
            ],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000', 'max:2099'],
            'remarks' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
