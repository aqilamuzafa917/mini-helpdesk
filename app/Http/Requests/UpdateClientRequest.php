<?php

namespace App\Http\Requests;

use App\Enums\ClientStatus;
use App\Enums\Role;
use App\Models\Client;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateClientRequest extends FormRequest
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
        $client = $this->route('client');
        $clientId = $client instanceof Client ? $client->id : $client;

        if (empty($clientId)) {
            $clientId = $this->input('id') ?? $this->input('client_id');
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:clients,email,'.$clientId,
            ],
            'phone' => ['required', 'string', 'max:255'],
            'status' => ['required', new Enum(ClientStatus::class)],
        ];
    }
}
