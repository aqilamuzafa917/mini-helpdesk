<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Monthly Support Report') }}</flux:heading>
            <flux:subheading>{{ __('Generate support ticket performance summaries and audit metrics.') }}</flux:subheading>
        </div>
        
        @if ($report)
            <flux:button 
                href="{{ route('reports.monthly.print', ['client_id' => $client_id, 'month' => $month, 'year' => $year]) }}" 
                target="_blank" 
                variant="primary" 
                icon="printer"
            >
                {{ __('Print Report') }}
            </flux:button>
        @endif
    </div>

    <!-- Filters Panel -->
    <div class="bg-zinc-50 dark:bg-zinc-900/50 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 flex flex-col sm:flex-row sm:items-center gap-4">
        @php $user = auth()->user(); @endphp

        <!-- Client Selector (Admin Only) -->
        @if ($user->role === \App\Enums\Role::Admin)
            <div class="flex-1 min-w-[200px] flex items-center gap-2">
                <flux:text class="text-sm font-medium whitespace-nowrap">{{ __('Client Company:') }}</flux:text>
                <select 
                    wire:model.live="client_id" 
                    class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-800 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:focus:border-zinc-500"
                >
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>
        @else
            <div class="flex-1">
                <flux:text class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                    {{ __('Client:') }} <span class="text-zinc-500 font-normal">{{ $user->client?->name }}</span>
                </flux:text>
            </div>
        @endif

        <!-- Month Selector -->
        <div class="flex items-center gap-2">
            <flux:text class="text-sm font-medium whitespace-nowrap">{{ __('Month:') }}</flux:text>
            <select 
                wire:model.live="month" 
                class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-800 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:focus:border-zinc-500"
            >
                @foreach ($months as $val => $name)
                    <option value="{{ $val }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Year Selector -->
        <div class="flex items-center gap-2">
            <flux:text class="text-sm font-medium whitespace-nowrap">{{ __('Year:') }}</flux:text>
            <select 
                wire:model.live="year" 
                class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-800 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:focus:border-zinc-500"
            >
                @foreach ($years as $val => $name)
                    <option value="{{ $val }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Report Body -->
    @if ($report)
        <div class="space-y-6">
            <!-- Summary Metrics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Card: Total Volume -->
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-xs">
                    <flux:heading size="md" level="3" class="font-semibold text-zinc-900 dark:text-zinc-50 mb-3">
                        {{ __('Ticket Volume') }}
                    </flux:heading>
                    <div class="flex items-baseline gap-2">
                        <span class="text-4xl font-extrabold text-indigo-600 dark:text-indigo-400">{{ $report['total_count'] }}</span>
                        <span class="text-xs text-zinc-400 uppercase font-semibold tracking-wider">{{ __('Tickets Created') }}</span>
                    </div>
                </div>

                <!-- Card: Status Counts -->
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-xs space-y-2 text-sm">
                    <flux:heading size="md" level="3" class="font-semibold text-zinc-900 dark:text-zinc-50 mb-2">
                        {{ __('Status Breakdown') }}
                    </flux:heading>
                    <div class="flex justify-between items-center">
                        <span class="text-zinc-500">{{ __('Open:') }}</span>
                        <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ $report['status_counts']['open'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-zinc-500">{{ __('In Progress:') }}</span>
                        <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ $report['status_counts']['in_progress'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-zinc-500">{{ __('Resolved:') }}</span>
                        <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ $report['status_counts']['resolved'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-zinc-500">{{ __('Closed:') }}</span>
                        <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ $report['status_counts']['closed'] ?? 0 }}</span>
                    </div>
                </div>

                <!-- Card: Priority Counts -->
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-xs space-y-2 text-sm">
                    <flux:heading size="md" level="3" class="font-semibold text-zinc-900 dark:text-zinc-50 mb-2">
                        {{ __('Priority Breakdown') }}
                    </flux:heading>
                    <div class="flex justify-between items-center">
                        <span class="text-zinc-500">{{ __('Low:') }}</span>
                        <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ $report['priority_counts']['low'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-zinc-500">{{ __('Medium:') }}</span>
                        <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ $report['priority_counts']['medium'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-zinc-500">{{ __('High:') }}</span>
                        <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ $report['priority_counts']['high'] ?? 0 }}</span>
                    </div>
                </div>
            </div>

            <!-- Admin Remarks Section -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6 shadow-xs space-y-4">
                <flux:heading size="md" level="3" class="font-semibold text-zinc-900 dark:text-zinc-50 pb-2 border-b border-zinc-100 dark:border-zinc-800">
                    {{ __('Executive Summary & Remarks') }}
                </flux:heading>

                @if ($user->role === \App\Enums\Role::Admin)
                    @if (session()->has('report_status'))
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-800 dark:text-emerald-300 rounded-lg border border-emerald-100 dark:border-emerald-900 text-xs">
                            {{ session('report_status') }}
                        </div>
                    @endif

                    <form wire:submit="saveRemarks" class="space-y-4">
                        <flux:field>
                            <flux:textarea 
                                wire:model="remarks" 
                                placeholder="{{ __('Enter monthly review notes, remarks, SLA updates, or comments for this client...') }}" 
                                rows="4" 
                            />
                            <flux:error name="remarks" />
                        </flux:field>
                        
                        <div class="flex justify-end">
                            <flux:button type="submit" variant="primary" size="sm">
                                {{ __('Save Remarks') }}
                            </flux:button>
                        </div>
                    </form>
                @else
                    <div class="text-sm text-zinc-600 dark:text-zinc-400 whitespace-pre-line leading-relaxed italic">
                        {{ $remarks ?: __('No remarks or summary notes provided for this month.') }}
                    </div>
                @endif
            </div>

            <!-- Tickets Detailed List -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xs overflow-hidden">
                <div class="p-6 border-b border-zinc-200 dark:border-zinc-800">
                    <flux:heading size="md" level="3" class="font-semibold text-zinc-900 dark:text-zinc-50">
                        {{ __('Tickets Audited (Created in This Period)') }}
                    </flux:heading>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-zinc-500 dark:text-zinc-400">
                        <thead class="bg-zinc-50 dark:bg-zinc-800 text-xs uppercase text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700">
                            <tr>
                                <th scope="col" class="px-6 py-4 font-semibold">{{ __('Ticket No.') }}</th>
                                <th scope="col" class="px-6 py-4 font-semibold">{{ __('Title') }}</th>
                                <th scope="col" class="px-6 py-4 font-semibold">{{ __('Assigned Engineer') }}</th>
                                <th scope="col" class="px-6 py-4 font-semibold">{{ __('Priority') }}</th>
                                <th scope="col" class="px-6 py-4 font-semibold">{{ __('Status') }}</th>
                                <th scope="col" class="px-6 py-4 font-semibold">{{ __('Created At') }}</th>
                                <th scope="col" class="px-6 py-4 font-semibold">{{ __('Resolved At') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @forelse ($report['tickets'] as $t)
                                <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors" wire:key="ticket-row-{{ $t->id }}">
                                    <!-- Ticket Number -->
                                    <td class="px-6 py-4 font-bold text-zinc-900 dark:text-zinc-100 whitespace-nowrap">
                                        <flux:link href="{{ route('tickets.show', $t) }}" class="hover:underline font-mono" wire:navigate>
                                            {{ $t->ticket_number }}
                                        </flux:link>
                                    </td>
                                    <!-- Title -->
                                    <td class="px-6 py-4 max-w-xs truncate font-medium text-zinc-800 dark:text-zinc-200">
                                        {{ $t->title }}
                                    </td>
                                    <!-- Assigned Engineer -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($t->assignedEngineer)
                                            <div class="flex items-center gap-2">
                                                <flux:avatar :name="$t->assignedEngineer->name" :initials="$t->assignedEngineer->initials()" size="xs" />
                                                <span>{{ $t->assignedEngineer->name }}</span>
                                            </div>
                                        @else
                                            <span class="text-zinc-400 dark:text-zinc-600 italic">{{ __('Unassigned') }}</span>
                                        @endif
                                    </td>
                                    <!-- Priority -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($t->priority === \App\Enums\Priority::Low)
                                            <flux:badge color="zinc" size="sm">{{ __('Low') }}</flux:badge>
                                        @elseif ($t->priority === \App\Enums\Priority::Medium)
                                            <flux:badge color="amber" size="sm">{{ __('Medium') }}</flux:badge>
                                        @else
                                            <flux:badge color="rose" size="sm">{{ __('High') }}</flux:badge>
                                        @endif
                                    </td>
                                    <!-- Status -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($t->status === \App\Enums\TicketStatus::Open)
                                            <flux:badge color="blue" size="sm">{{ __('Open') }}</flux:badge>
                                        @elseif ($t->status === \App\Enums\TicketStatus::InProgress)
                                            <flux:badge color="orange" size="sm">{{ __('In Progress') }}</flux:badge>
                                        @elseif ($t->status === \App\Enums\TicketStatus::Resolved)
                                            <flux:badge color="green" size="sm">{{ __('Resolved') }}</flux:badge>
                                        @else
                                            <flux:badge color="zinc" size="sm">{{ __('Closed') }}</flux:badge>
                                        @endif
                                    </td>
                                    <!-- Created At -->
                                    <td class="px-6 py-4 whitespace-nowrap text-zinc-400 dark:text-zinc-500 text-xs">
                                        {{ $t->created_at->format('M d, Y H:i') }}
                                    </td>
                                    <!-- Resolved At -->
                                    <td class="px-6 py-4 whitespace-nowrap text-zinc-400 dark:text-zinc-500 text-xs">
                                        {{ $t->resolved_at ? $t->resolved_at->format('M d, Y H:i') : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-zinc-400 dark:text-zinc-500">
                                        <div class="flex flex-col items-center justify-center space-y-2">
                                            <flux:icon icon="ticket" class="size-8 text-zinc-300 dark:text-zinc-600" />
                                            <span class="text-sm">{{ __('No tickets created during this period.') }}</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    @else
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-12 text-center text-zinc-400 dark:text-zinc-500 shadow-xs">
            <div class="flex flex-col items-center justify-center space-y-2">
                <flux:icon icon="chart-bar" class="size-8 text-zinc-300 dark:text-zinc-600" />
                <span class="text-sm">{{ __('No client selected or report parameters are invalid.') }}</span>
            </div>
        </div>
    @endif
</div>
