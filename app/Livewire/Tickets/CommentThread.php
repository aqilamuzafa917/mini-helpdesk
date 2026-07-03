<?php

namespace App\Livewire\Tickets;

use App\Enums\Role;
use App\Http\Requests\StoreCommentRequest;
use App\Models\Ticket;
use App\Models\TicketComment;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

class CommentThread extends Component
{
    /**
     * The Ticket model instance.
     */
    public Ticket $ticket;

    /**
     * Form inputs.
     */
    public string $comment = '';

    public bool $is_internal = false;

    /**
     * Mount the component.
     */
    public function mount(Ticket $ticket): void
    {
        $this->ticket = $ticket;
    }

    /**
     * Submit a new comment.
     *
     * @return void
     */
    public function addComment()
    {
        // Authorize comment creation using the TicketCommentPolicy
        Gate::authorize('create', [TicketComment::class, $this->ticket, $this->is_internal]);

        $user = auth()->user();

        // Perform request-level validation
        $request = new StoreCommentRequest;
        $payload = [
            'ticket_id' => $this->ticket->id,
            'comment' => $this->comment,
            'is_internal' => $this->is_internal,
        ];
        $request->merge($payload);

        if (! $request->authorize()) {
            abort(403, 'Unauthorized action.');
        }

        $this->validate($request->rules());

        TicketComment::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $user->id,
            'comment' => $this->comment,
            'is_internal' => $user->role === Role::Client ? false : $this->is_internal,
        ]);

        $this->comment = '';
        $this->is_internal = false;

        session()->flash('comment_status', 'Comment added successfully.');
    }

    /**
     * Render the component.
     *
     * @return View
     */
    public function render()
    {
        $user = auth()->user();

        // Build base comments query
        $query = $this->ticket->comments()->with('user')->orderBy('created_at', 'asc');

        // Hide internal comments from Clients
        if ($user->role === Role::Client) {
            $query->where('is_internal', false);
        }

        return view('livewire.tickets.comment-thread', [
            'comments' => $query->get(),
        ]);
    }
}
