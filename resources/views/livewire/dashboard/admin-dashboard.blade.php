<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Admin Dashboard') }}</flux:heading>
            <flux:subheading>{{ __('Overview of IT helpdesk metrics, ticket distribution, and recent queue updates.') }}</flux:subheading>
        </div>
    </div>

    <!-- Metrics Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Card: Total Tickets -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-xs flex items-center gap-4">
            <div class="p-3 bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 rounded-lg">
                <flux:icon icon="ticket" class="size-6" />
            </div>
            <div>
                <span class="text-xs text-zinc-400 dark:text-zinc-500 font-semibold uppercase tracking-wider block">{{ __('Total Tickets') }}</span>
                <span class="text-2xl font-bold text-zinc-900 dark:text-zinc-50">{{ $metrics['total_count'] }}</span>
            </div>
        </div>

        <!-- Card: Open Tickets -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-xs flex items-center gap-4">
            <div class="p-3 bg-blue-50 dark:bg-blue-950/30 text-blue-600 dark:text-blue-400 rounded-lg">
                <flux:icon icon="envelope-open" class="size-6" />
            </div>
            <div>
                <span class="text-xs text-zinc-400 dark:text-zinc-500 font-semibold uppercase tracking-wider block">{{ __('Open Tickets') }}</span>
                <span class="text-2xl font-bold text-zinc-900 dark:text-zinc-50">{{ $metrics['open_count'] }}</span>
            </div>
        </div>

        <!-- Card: Unassigned Tickets -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-xs flex items-center gap-4">
            <div class="p-3 bg-amber-50 dark:bg-amber-950/30 text-amber-600 dark:text-amber-400 rounded-lg">
                <flux:icon icon="user-minus" class="size-6" />
            </div>
            <div>
                <span class="text-xs text-zinc-400 dark:text-zinc-500 font-semibold uppercase tracking-wider block">{{ __('Unassigned') }}</span>
                <span class="text-2xl font-bold text-zinc-900 dark:text-zinc-50">{{ $metrics['unassigned_count'] }}</span>
            </div>
        </div>

        <!-- Card: Resolved Tickets -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-xs flex items-center gap-4">
            <div class="p-3 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 rounded-lg">
                <flux:icon icon="check-circle" class="size-6" />
            </div>
            <div>
                <span class="text-xs text-zinc-400 dark:text-zinc-500 font-semibold uppercase tracking-wider block">{{ __('Resolved') }}</span>
                <span class="text-2xl font-bold text-zinc-900 dark:text-zinc-50">{{ $metrics['status_counts']['resolved'] ?? 0 }}</span>
            </div>
        </div>
    </div>

    <!-- Status Breakdown visual progress bar -->
    @php
        $total = $metrics['total_count'];
        $open = $metrics['status_counts']['open'] ?? 0;
        $inProgress = $metrics['status_counts']['in_progress'] ?? 0;
        $resolved = $metrics['status_counts']['resolved'] ?? 0;
        $closed = $metrics['status_counts']['closed'] ?? 0;

        $openPct = $total > 0 ? round(($open / $total) * 100) : 0;
        $inProgressPct = $total > 0 ? round(($inProgress / $total) * 100) : 0;
        $resolvedPct = $total > 0 ? round(($resolved / $total) * 100) : 0;
        $closedPct = $total > 0 ? round(($closed / $total) * 100) : 0;
    @endphp

    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6 shadow-xs space-y-4">
        <flux:heading size="md" level="2" class="font-semibold text-zinc-900 dark:text-zinc-50">
            {{ __('Ticket Status Breakdown') }}
        </flux:heading>

        <!-- Horizontal Bar -->
        <div class="h-4 w-full flex rounded-full overflow-hidden bg-zinc-100 dark:bg-zinc-800">
            @if ($total > 0)
                <div style="width: {{ $openPct }}%" class="bg-blue-500 transition-all" title="Open: {{ $openPct }}%"></div>
                <div style="width: {{ $inProgressPct }}%" class="bg-orange-500 transition-all" title="In Progress: {{ $inProgressPct }}%"></div>
                <div style="width: {{ $resolvedPct }}%" class="bg-green-500 transition-all" title="Resolved: {{ $resolvedPct }}%"></div>
                <div style="width: {{ $closedPct }}%" class="bg-zinc-500 transition-all" title="Closed: {{ $closedPct }}%"></div>
            @else
                <div class="w-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center text-[10px] text-zinc-400">
                    {{ __('No ticket data available') }}
                </div>
            @endif
        </div>

        <!-- Legend -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-2">
            <div class="flex items-center gap-2">
                <span class="size-3 rounded-full bg-blue-500"></span>
                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('Open:') }} <span class="font-bold">{{ $open }}</span> <span class="text-xs text-zinc-400 font-normal">({{ $openPct }}%)</span>
                </span>
            </div>
            <div class="flex items-center gap-2">
                <span class="size-3 rounded-full bg-orange-500"></span>
                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('In Progress:') }} <span class="font-bold">{{ $inProgress }}</span> <span class="text-xs text-zinc-400 font-normal">({{ $inProgressPct }}%)</span>
                </span>
            </div>
            <div class="flex items-center gap-2">
                <span class="size-3 rounded-full bg-green-500"></span>
                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('Resolved:') }} <span class="font-bold">{{ $resolved }}</span> <span class="text-xs text-zinc-400 font-normal">({{ $resolvedPct }}%)</span>
                </span>
            </div>
            <div class="flex items-center gap-2">
                <span class="size-3 rounded-full bg-zinc-500"></span>
                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('Closed:') }} <span class="font-bold">{{ $closed }}</span> <span class="text-xs text-zinc-400 font-normal">({{ $closedPct }}%)</span>
                </span>
            </div>
        </div>
    </div>

    <!-- Recent Tickets Section -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xs overflow-hidden">
        <div class="p-6 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
            <flux:heading size="md" level="2" class="font-semibold text-zinc-900 dark:text-zinc-50">
                {{ __('Recently Updated Tickets') }}
            </flux:heading>
            <flux:link href="{{ route('tickets.index') }}" class="text-sm font-semibold hover:underline" wire:navigate>
                {{ __('View All Tickets') }}
            </flux:link>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-zinc-500 dark:text-zinc-400">
                <thead class="bg-zinc-50 dark:bg-zinc-800 text-xs uppercase text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700">
                    <tr>
                        <th scope="col" class="px-6 py-4 font-semibold">{{ __('Ticket No.') }}</th>
                        <th scope="col" class="px-6 py-4 font-semibold">{{ __('Title') }}</th>
                        <th scope="col" class="px-6 py-4 font-semibold">{{ __('Client') }}</th>
                        <th scope="col" class="px-6 py-4 font-semibold">{{ __('Assigned Engineer') }}</th>
                        <th scope="col" class="px-6 py-4 font-semibold">{{ __('Status') }}</th>
                        <th scope="col" class="px-6 py-4 font-semibold">{{ __('Last Updated') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($recentTickets as $ticket)
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
                            <!-- Client -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $ticket->client?->name ?? '—' }}
                            </td>
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
                            <!-- Last Updated -->
                            <td class="px-6 py-4 whitespace-nowrap text-zinc-400 dark:text-zinc-500 text-xs">
                                {{ $ticket->updated_at->diffForHumans() }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-zinc-400 dark:text-zinc-500">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <flux:icon icon="ticket" class="size-8 text-zinc-300 dark:text-zinc-600" />
                                    <span class="text-sm">{{ __('No tickets created yet.') }}</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
