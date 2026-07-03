<div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">
                {{ $isEdit ? __('Edit Ticket') : __('Create Ticket') }}
            </flux:heading>
            <flux:subheading>
                {{ $isEdit ? __('Update support ticket progress or assign engineer.') : __('Submit a new IT support request.') }}
            </flux:subheading>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl p-6 shadow-sm">
        <form wire:submit="save" class="space-y-6">
            @php $user = auth()->user(); @endphp

            @if ($isEdit && $user->role === \App\Enums\Role::Engineer)
                <!-- READ-ONLY SECTION FOR ENGINEER -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-zinc-50 dark:bg-zinc-900/50 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 mb-6 text-sm">
                    <div>
                        <flux:text class="text-xs font-semibold uppercase tracking-wider text-zinc-400 block mb-1">{{ __('Ticket Number') }}</flux:text>
                        <span class="font-mono font-bold text-zinc-950 dark:text-zinc-50">{{ $ticket->ticket_number }}</span>
                    </div>
                    <div>
                        <flux:text class="text-xs font-semibold uppercase tracking-wider text-zinc-400 block mb-1">{{ __('Client Company') }}</flux:text>
                        <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $ticket->client?->name ?? '—' }}</span>
                    </div>
                    <div class="md:col-span-2">
                        <flux:text class="text-xs font-semibold uppercase tracking-wider text-zinc-400 block mb-1">{{ __('Title') }}</flux:text>
                        <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $ticket->title }}</span>
                    </div>
                    <div class="md:col-span-2">
                        <flux:text class="text-xs font-semibold uppercase tracking-wider text-zinc-400 block mb-1">{{ __('Description') }}</flux:text>
                        <span class="text-zinc-600 dark:text-zinc-400 block whitespace-pre-line">{{ $ticket->description }}</span>
                    </div>
                    <div>
                        <flux:text class="text-xs font-semibold uppercase tracking-wider text-zinc-400 block mb-1">{{ __('Priority') }}</flux:text>
                        @if ($ticket->priority === \App\Enums\Priority::Low)
                            <flux:badge color="zinc" size="sm">{{ __('Low') }}</flux:badge>
                        @elseif ($ticket->priority === \App\Enums\Priority::Medium)
                            <flux:badge color="amber" size="sm">{{ __('Medium') }}</flux:badge>
                        @else
                            <flux:badge color="rose" size="sm">{{ __('High') }}</flux:badge>
                        @endif
                    </div>
                    <div>
                        <flux:text class="text-xs font-semibold uppercase tracking-wider text-zinc-400 block mb-1">{{ __('Created At') }}</flux:text>
                        <span class="text-zinc-500 dark:text-zinc-500">{{ $ticket->created_at->format('M d, Y H:i') }}</span>
                    </div>
                </div>
            @endif

            @if ($user->role === \App\Enums\Role::Admin || ($user->role === \App\Enums\Role::Client && !$isEdit))
                <!-- TITLE -->
                <flux:field>
                    <flux:label>{{ __('Ticket Title') }}</flux:label>
                    <flux:input wire:model="title" placeholder="{{ __('Describe the issue in a few words...') }}" required />
                    <flux:error name="title" />
                </flux:field>

                <!-- DESCRIPTION -->
                <flux:field>
                    <flux:label>{{ __('Description') }}</flux:label>
                    <flux:textarea wire:model="description" placeholder="{{ __('Provide detailed steps to reproduce, errors seen, or hardware details...') }}" rows="5" required />
                    <flux:error name="description" />
                </flux:field>

                <!-- PRIORITY -->
                <flux:field>
                    <flux:label>{{ __('Priority') }}</flux:label>
                    <flux:select wire:model="priority">
                        <option value="low">{{ __('Low') }}</option>
                        <option value="medium">{{ __('Medium') }}</option>
                        <option value="high">{{ __('High') }}</option>
                    </flux:select>
                    <flux:error name="priority" />
                </flux:field>
            @endif

            @if ($user->role === \App\Enums\Role::Admin)
                <!-- CLIENT SELECTION (Admin only) -->
                <flux:field>
                    <flux:label>{{ __('Client Company') }}</flux:label>
                    <flux:select wire:model="client_id" placeholder="{{ __('Select associated client company...') }}">
                        @foreach ($clients as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="client_id" />
                </flux:field>

                <!-- ASSIGNED ENGINEER (Admin only) -->
                <flux:field>
                    <flux:label>{{ __('Assign Engineer') }}</flux:label>
                    <flux:select wire:model="assigned_engineer_id" placeholder="{{ __('Select engineer to assign...') }}">
                        @foreach ($engineers as $e)
                            <option value="{{ $e->id }}">{{ $e->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="assigned_engineer_id" />
                </flux:field>
            @endif

            @if ($user->role === \App\Enums\Role::Admin || $user->role === \App\Enums\Role::Engineer)
                <!-- STATUS (Admin & Engineer only) -->
                <flux:field>
                    <flux:label>{{ __('Ticket Status') }}</flux:label>
                    <flux:select wire:model="status">
                        <option value="open">{{ __('Open') }}</option>
                        <option value="in_progress">{{ __('In Progress') }}</option>
                        <option value="resolved">{{ __('Resolved') }}</option>
                        <option value="closed">{{ __('Closed') }}</option>
                    </flux:select>
                    <flux:error name="status" />
                </flux:field>

                <!-- STATUS CHANGE NOTES (Admin & Engineer only) -->
                <flux:field>
                    <flux:label>{{ __('Status Change Notes') }}</flux:label>
                    <flux:textarea wire:model="status_change_notes" placeholder="{{ __('Provide details or explanation for the status change...') }}" rows="3" />
                    <flux:error name="status_change_notes" />
                </flux:field>
            @endif

            <!-- Form Actions -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                <flux:button href="{{ route('tickets.index') }}" variant="subtle" wire:navigate>
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button type="submit" variant="primary">
                    {{ $isEdit ? __('Save Changes') : __('Create Ticket') }}
                </flux:button>
            </div>
        </form>
    </div>
</div>
