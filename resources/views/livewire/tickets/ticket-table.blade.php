<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Tickets') }}</flux:heading>
            <flux:subheading>{{ __('Browse support tickets, track status, and monitor resolution progress.') }}</flux:subheading>
        </div>
        @can('create', \App\Models\Ticket::class)
            <flux:button href="{{ route('tickets.create') }}" variant="primary" icon="plus" wire:navigate>
                {{ __('Create Ticket') }}
            </flux:button>
        @endcan
    </div>

    <!-- Filters -->
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center bg-zinc-50 dark:bg-zinc-900/50 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700">
        <!-- Search -->
        <div class="flex-1 min-w-[200px]">
            <flux:input 
                wire:model.live.debounce.300ms="search" 
                placeholder="{{ __('Search by ticket number, title, or description...') }}" 
                icon="magnifying-glass" 
                clearable 
            />
        </div>

        <!-- Filters Grid -->
        <div class="flex flex-wrap items-center gap-4">
            <!-- Status Filter -->
            <div class="flex items-center gap-2">
                <flux:text class="text-sm font-medium whitespace-nowrap">{{ __('Status:') }}</flux:text>
                <select 
                    wire:model.live="statusFilter" 
                    class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-800 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:focus:border-zinc-500"
                >
                    <option value="">{{ __('All Statuses') }}</option>
                    <option value="open">{{ __('Open') }}</option>
                    <option value="in_progress">{{ __('In Progress') }}</option>
                    <option value="resolved">{{ __('Resolved') }}</option>
                    <option value="closed">{{ __('Closed') }}</option>
                </select>
            </div>

            <!-- Priority Filter -->
            <div class="flex items-center gap-2">
                <flux:text class="text-sm font-medium whitespace-nowrap">{{ __('Priority:') }}</flux:text>
                <select 
                    wire:model.live="priorityFilter" 
                    class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-800 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:focus:border-zinc-500"
                >
                    <option value="">{{ __('All Priorities') }}</option>
                    <option value="low">{{ __('Low') }}</option>
                    <option value="medium">{{ __('Medium') }}</option>
                    <option value="high">{{ __('High') }}</option>
                </select>
            </div>

            <!-- Client Filter (Admin Only) -->
            @if (auth()->user()->role === \App\Enums\Role::Admin)
                <div class="flex items-center gap-2">
                    <flux:text class="text-sm font-medium whitespace-nowrap">{{ __('Client:') }}</flux:text>
                    <select 
                        wire:model.live="clientFilter" 
                        class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-800 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:focus:border-zinc-500"
                    >
                        <option value="">{{ __('All Clients') }}</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
    </div>

    <!-- Notification Alert -->
    @if (session()->has('status'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-800 dark:text-emerald-300 rounded-lg border border-emerald-200 dark:border-emerald-800">
            <span class="text-sm">{{ session('status') }}</span>
        </div>
    @endif

    <!-- Table -->
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
        <table class="w-full text-left text-sm text-zinc-500 dark:text-zinc-400">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-xs uppercase text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700">
                <tr>
                    <th scope="col" class="px-6 py-4 font-semibold">{{ __('Ticket No.') }}</th>
                    <th scope="col" class="px-6 py-4 font-semibold">{{ __('Title') }}</th>
                    @if (auth()->user()->role === \App\Enums\Role::Admin || auth()->user()->role === \App\Enums\Role::Engineer)
                        <th scope="col" class="px-6 py-4 font-semibold">{{ __('Client') }}</th>
                    @endif
                    <th scope="col" class="px-6 py-4 font-semibold">{{ __('Assigned Engineer') }}</th>
                    <th scope="col" class="px-6 py-4 font-semibold">{{ __('Priority') }}</th>
                    <th scope="col" class="px-6 py-4 font-semibold">{{ __('Status') }}</th>
                    <th scope="col" class="px-6 py-4 font-semibold">{{ __('Created At') }}</th>
                    <th scope="col" class="px-6 py-4 font-semibold text-right">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($tickets as $ticket)
                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors" wire:key="ticket-row-{{ $ticket->id }}">
                        <!-- Ticket Number -->
                        <td class="px-6 py-4 font-bold text-zinc-900 dark:text-zinc-100 whitespace-nowrap">
                            <flux:link href="{{ route('tickets.show', $ticket) }}" class="hover:underline font-mono" wire:navigate>
                                {{ $ticket->ticket_number }}
                            </flux:link>
                        </td>
                        <!-- Title -->
                        <td class="px-6 py-4 max-w-xs truncate font-medium text-zinc-800 dark:text-zinc-200">
                            {{ $ticket->title }}
                        </td>
                        <!-- Client (Admin/Engineer only) -->
                        @if (auth()->user()->role === \App\Enums\Role::Admin || auth()->user()->role === \App\Enums\Role::Engineer)
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $ticket->client?->name ?? '—' }}
                            </td>
                        @endif
                        <!-- Assigned Engineer -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($ticket->assignedEngineer)
                                <div class="flex items-center gap-2">
                                    <flux:avatar :name="$ticket->assignedEngineer->name" :initials="$ticket->assignedEngineer->initials()" size="xs" />
                                    <span>{{ $ticket->assignedEngineer->name }}</span>
                                </div>
                            @else
                                <span class="text-zinc-400 dark:text-zinc-600 italic">{{ __('Unassigned') }}</span>
                            @endif
                        </td>
                        <!-- Priority -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($ticket->priority === \App\Enums\Priority::Low)
                                <flux:badge color="zinc" size="sm">{{ __('Low') }}</flux:badge>
                            @elseif ($ticket->priority === \App\Enums\Priority::Medium)
                                <flux:badge color="amber" size="sm">{{ __('Medium') }}</flux:badge>
                            @else
                                <flux:badge color="rose" size="sm">{{ __('High') }}</flux:badge>
                            @endif
                        </td>
                        <!-- Status -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($ticket->status === \App\Enums\TicketStatus::Open)
                                <flux:badge color="blue" size="sm">{{ __('Open') }}</flux:badge>
                            @elseif ($ticket->status === \App\Enums\TicketStatus::InProgress)
                                <flux:badge color="orange" size="sm">{{ __('In Progress') }}</flux:badge>
                            @elseif ($ticket->status === \App\Enums\TicketStatus::Resolved)
                                <flux:badge color="green" size="sm">{{ __('Resolved') }}</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">{{ __('Closed') }}</flux:badge>
                            @endif
                        </td>
                        <!-- Created At -->
                        <td class="px-6 py-4 whitespace-nowrap text-zinc-400 dark:text-zinc-500 text-xs">
                            {{ $ticket->created_at->format('M d, Y H:i') }}
                        </td>
                        <!-- Actions -->
                        <td class="px-6 py-4 text-right whitespace-nowrap space-x-2">
                            <flux:button 
                                href="{{ route('tickets.show', $ticket) }}" 
                                size="sm" 
                                variant="subtle" 
                                icon="eye" 
                                wire:navigate 
                                :label="__('View')"
                            />
                            @can('update', $ticket)
                                <flux:button 
                                    href="{{ route('tickets.edit', $ticket) }}" 
                                    size="sm" 
                                    variant="subtle" 
                                    icon="pencil" 
                                    wire:navigate 
                                    :label="__('Edit')"
                                />
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-zinc-400 dark:text-zinc-500">
                            <div class="flex flex-col items-center justify-center space-y-2">
                                <flux:icon icon="ticket" class="size-8 text-zinc-300 dark:text-zinc-600" />
                                <span class="text-sm">{{ __('No tickets found matching the filters.') }}</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pt-4">
        {{ $tickets->links() }}
    </div>
</div>
