<?php

namespace App\Http\Requests;

use App\Enums\Priority;
use App\Enums\Role;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateTicketRequest extends FormRequest
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

        // Admin has full modification access
        if ($user->role === Role::Admin) {
            return true;
        }

        // Clients are not authorized to update tickets (returns 403)
        if ($user->role === Role::Client) {
            return false;
        }

        // Engineer checks
        if ($user->role === Role::Engineer) {
            $ticket = $this->route('ticket');

            // Fallback for Livewire stateless requests
            if (! $ticket instanceof Ticket) {
                $ticketId = $this->input('id') ?? $this->input('ticket_id');
                $ticket = Ticket::find($ticketId);
            }

            if (! $ticket) {
                return false;
            }

            // Must be the assigned engineer
            if ($ticket->assigned_engineer_id !== $user->id) {
                return false;
            }

            // Requirement 3: cannot change assigned_engineer_id
            if ($this->has('assigned_engineer_id') && (int) $this->input('assigned_engineer_id') !== (int) $ticket->assigned_engineer_id) {
                return false;
            }

            // Requirement 3: cannot change priority
            if ($this->has('priority') && $this->input('priority') !== $ticket->priority->value) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = auth()->user();

        if ($user && $user->role === Role::Engineer) {
            return [
                'status' => ['required', new Enum(TicketStatus::class)],
                'status_change_notes' => ['nullable', 'string', 'max:1000'],
            ];
        }

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['required', new Enum(Priority::class)],
            'status' => ['required', new Enum(TicketStatus::class)],
            'client_id' => ['required', 'exists:clients,id'],
            'assigned_engineer_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('role', Role::Engineer->value),
            ],
            'status_change_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
