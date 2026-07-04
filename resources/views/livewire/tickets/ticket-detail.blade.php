<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between pb-6 border-b border-zinc-200 dark:border-zinc-800">
        <div>
            <div class="flex items-center gap-3 mb-2 flex-wrap">
                <span class="font-mono font-bold text-lg text-zinc-500 dark:text-zinc-400 bg-zinc-100 dark:bg-zinc-800 px-2.5 py-1 rounded-md">
                    {{ $ticket->ticket_number }}
                </span>
                
                <!-- Priority Badge -->
                @if ($ticket->priority === \App\Enums\Priority::Low)
                    <flux:badge color="zinc" size="sm">{{ __('Low Priority') }}</flux:badge>
                @elseif ($ticket->priority === \App\Enums\Priority::Medium)
                    <flux:badge color="amber" size="sm">{{ __('Medium Priority') }}</flux:badge>
                @else
                    <flux:badge color="rose" size="sm">{{ __('High Priority') }}</flux:badge>
                @endif

                <!-- Status Badge -->
                @if ($ticket->status === \App\Enums\TicketStatus::Open)
                    <flux:badge color="blue" size="sm">{{ __('Open') }}</flux:badge>
                @elseif ($ticket->status === \App\Enums\TicketStatus::InProgress)
                    <flux:badge color="orange" size="sm">{{ __('In Progress') }}</flux:badge>
                @elseif ($ticket->status === \App\Enums\TicketStatus::Resolved)
                    <flux:badge color="green" size="sm">{{ __('Resolved') }}</flux:badge>
                @else
                    <flux:badge color="zinc" size="sm">{{ __('Closed') }}</flux:badge>
                @endif
            </div>
            <flux:heading size="xl" level="1" class="font-bold text-zinc-900 dark:text-zinc-50">
                {{ $ticket->title }}
            </flux:heading>
        </div>
        
        <div class="flex items-center gap-3">
            <flux:button href="{{ route('tickets.index') }}" variant="subtle" icon="arrow-left" wire:navigate>
                {{ __('Back to Tickets') }}
            </flux:button>
            @can('update', $ticket)
                <flux:button href="{{ route('tickets.edit', $ticket) }}" variant="primary" icon="pencil" wire:navigate>
                    {{ __('Edit Ticket') }}
                </flux:button>
            @endcan
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left Side: Ticket Description & Comments (2 Cols) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Ticket Description Card -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6 shadow-xs">
                <flux:heading size="lg" level="2" class="mb-4 font-semibold text-zinc-900 dark:text-zinc-50">
                    {{ __('Description') }}
                </flux:heading>
                <div class="text-zinc-700 dark:text-zinc-300 text-sm whitespace-pre-line leading-relaxed">
                    {{ $ticket->description }}
                </div>
            </div>

            <!-- Comment Thread Component -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6 shadow-xs">
                <livewire:tickets.comment-thread :ticket="$ticket" />
            </div>
        </div>

        <!-- Right Side: Sidebar Info & Status Updates (1 Col) -->
        <div class="space-y-6">
            
            <!-- Ticket Info Panel -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6 shadow-xs space-y-5">
                <flux:heading size="md" level="2" class="font-semibold text-zinc-900 dark:text-zinc-50 border-b border-zinc-100 dark:border-zinc-800 pb-3">
                    {{ __('Ticket Details') }}
                </flux:heading>

                @if (auth()->user()->role === \App\Enums\Role::Admin || auth()->user()->role === \App\Enums\Role::Engineer)
                    <div>
                        <flux:text class="text-xs font-semibold uppercase tracking-wider text-zinc-400 block mb-1">
                            {{ __('Client Company') }}
                        </flux:text>
                        <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                            {{ $ticket->client?->name ?? '—' }}
                        </span>
                    </div>
                @endif

                <div>
                    <flux:text class="text-xs font-semibold uppercase tracking-wider text-zinc-400 block mb-1">
                        {{ __('Submitted By') }}
                    </flux:text>
                    <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                        {{ $ticket->creator?->name ?? '—' }}
                    </span>
                </div>

                <div>
                    <flux:text class="text-xs font-semibold uppercase tracking-wider text-zinc-400 block mb-1">
                        {{ __('Assigned Engineer') }}
                    </flux:text>
                    @if ($ticket->assignedEngineer)
                        <div class="flex items-center gap-2 mt-1">
                            <flux:avatar :name="$ticket->assignedEngineer->name" :initials="$ticket->assignedEngineer->initials()" size="xs" />
                            <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                {{ $ticket->assignedEngineer->name }}
                            </span>
                        </div>
                    @else
                        <span class="text-sm text-zinc-400 dark:text-zinc-600 italic">
                            {{ __('Unassigned') }}
                        </span>
                    @endif
                </div>

                <div>
                    <flux:text class="text-xs font-semibold uppercase tracking-wider text-zinc-400 block mb-1">
                        {{ __('Created At') }}
                    </flux:text>
                    <span class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ $ticket->created_at->format('M d, Y H:i') }}
                    </span>
                </div>

                @if ($ticket->resolved_at)
                    <div>
                        <flux:text class="text-xs font-semibold uppercase tracking-wider text-zinc-400 block mb-1">
                            {{ __('Resolved At') }}
                        </flux:text>
                        <span class="text-sm text-emerald-600 dark:text-emerald-400 font-medium">
                            {{ $ticket->resolved_at->format('M d, Y H:i') }}
                        </span>
                    </div>
                @endif
            </div>

            <!-- Action: Update Status (Admin & Assigned Engineer only) -->
            @can('update', $ticket)
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6 shadow-xs space-y-4">
                    <flux:heading size="md" level="2" class="font-semibold text-zinc-900 dark:text-zinc-50 pb-2 border-b border-zinc-100 dark:border-zinc-800">
                        {{ __('Update Status') }}
                    </flux:heading>

                    @if (session()->has('status'))
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-800 dark:text-emerald-300 rounded-lg border border-emerald-100 dark:border-emerald-900 text-xs">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form wire:submit="updateStatus" class="space-y-4">
                        <flux:field>
                            <flux:label>{{ __('Select Status') }}</flux:label>
                            <flux:select wire:model="status">
                                <option value="open">{{ __('Open') }}</option>
                                <option value="in_progress">{{ __('In Progress') }}</option>
                                <option value="resolved">{{ __('Resolved') }}</option>
                                <option value="closed">{{ __('Closed') }}</option>
                            </flux:select>
                            <flux:error name="status" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('Notes / Reason') }}</flux:label>
                            <flux:textarea wire:model="status_change_notes" placeholder="{{ __('Why is this status being changed...') }}" rows="2" />
                            <flux:error name="status_change_notes" />
                        </flux:field>

                        <flux:button type="submit" variant="primary" size="sm" class="w-full">
                            {{ __('Update Status') }}
                        </flux:button>
                    </form>
                </div>
            @endcan

            <!-- Timeline Audit History -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6 shadow-xs space-y-4">
                <flux:heading size="md" level="2" class="font-semibold text-zinc-900 dark:text-zinc-50 border-b border-zinc-100 dark:border-zinc-800 pb-3">
                    {{ __('Status History') }}
                </flux:heading>

                <div class="relative pl-6 border-l-2 border-zinc-100 dark:border-zinc-800 space-y-6">
                    @forelse ($histories as $history)
                        <div class="relative">
                            <!-- Timeline Dot -->
                            <span class="absolute -left-[31px] top-1.5 flex h-4.5 w-4.5 items-center justify-center rounded-full bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700">
                                <span class="h-2 w-2 rounded-full bg-zinc-400"></span>
                            </span>
                            
                            <div class="text-xs text-zinc-400 dark:text-zinc-500 mb-1">
                                {{ $history->changed_at->diffForHumans() }}
                            </div>
                            
                            <div class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                                @if (empty($history->old_status))
                                    {{ __('Created as') }}
                                    <span class="font-bold text-zinc-900 dark:text-zinc-100">{{ strtoupper($history->new_status) }}</span>
                                @else
                                    <span class="text-zinc-400 line-through">{{ strtoupper($history->old_status) }}</span>
                                    <span class="text-zinc-400 mx-1">→</span>
                                    <span class="font-bold text-zinc-900 dark:text-zinc-100">{{ strtoupper($history->new_status) }}</span>
                                @endif
                            </div>

                            <div class="flex items-center gap-1.5 mt-1 text-xs text-zinc-500">
                                <span>by</span>
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ $history->changer?->name ?? __('System') }}
                                </span>
                            </div>

                            @if ($history->notes)
                                <div class="mt-2 p-2 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg text-xs text-zinc-600 dark:text-zinc-400 italic border border-zinc-100 dark:border-zinc-800">
                                    "{{ $history->notes }}"
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-xs text-zinc-400 italic">
                            {{ __('No status history recorded.') }}
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</div>
