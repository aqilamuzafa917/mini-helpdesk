<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\Ticket;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
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

        $ticket = $this->route('ticket');

        // Fallback for Livewire context
        if (! $ticket instanceof Ticket) {
            $ticketId = $this->input('ticket_id') ?? $this->input('id');
            $ticket = Ticket::find($ticketId);
        }

        if (! $ticket) {
            return false;
        }

        // Client authorization rules
        if ($user->role === Role::Client) {
            // Ticket must belong to the user's client
            if ($ticket->client_id !== $user->client_id) {
                return false;
            }

            // Requirement 16: Client must not set is_internal = true
            if ($this->boolean('is_internal')) {
                return false;
            }
        }

        // Engineer authorization rules
        if ($user->role === Role::Engineer) {
            // Must be the assigned engineer
            if ($ticket->assigned_engineer_id !== $user->id) {
                return false;
            }
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
        return [
            'comment' => ['required', 'string', 'min:1'],
            'is_internal' => ['nullable', 'boolean'],
        ];
    }
}
