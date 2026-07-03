<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Clients') }}</flux:heading>
            <flux:subheading>{{ __('Manage client companies and toggle their active/inactive status.') }}</flux:subheading>
        </div>
        <flux:button href="{{ route('clients.create') }}" variant="primary" icon="plus" wire:navigate>
            {{ __('Add Client') }}
        </flux:button>
    </div>

    <!-- Filters -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between bg-zinc-50 dark:bg-zinc-900/50 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700">
        <div class="flex-1 max-w-md">
            <flux:input 
                wire:model.live.debounce.300ms="search" 
                placeholder="{{ __('Search clients by name, contact, email, or phone...') }}" 
                icon="magnifying-glass" 
                clearable 
            />
        </div>
        <div class="flex items-center gap-3">
            <flux:text class="text-sm font-medium">{{ __('Status:') }}</flux:text>
            <select 
                wire:model.live="statusFilter" 
                class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-800 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:focus:border-zinc-500"
            >
                <option value="">{{ __('All Statuses') }}</option>
                <option value="active">{{ __('Active') }}</option>
                <option value="inactive">{{ __('Inactive') }}</option>
            </select>
        </div>
    </div>

    <!-- Flash Message -->
    @if (session()->has('status'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-800 dark:text-emerald-300 rounded-lg border border-emerald-200 dark:border-emerald-800 flex items-center justify-between">
            <span class="text-sm">{{ session('status') }}</span>
        </div>
    @endif

    <!-- Table -->
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
        <table class="w-full text-left text-sm text-zinc-500 dark:text-zinc-400">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-xs uppercase text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700">
                <tr>
                    <th scope="col" class="px-6 py-4 font-semibold">{{ __('Client Name') }}</th>
                    <th scope="col" class="px-6 py-4 font-semibold">{{ __('Contact Person') }}</th>
                    <th scope="col" class="px-6 py-4 font-semibold">{{ __('Email') }}</th>
                    <th scope="col" class="px-6 py-4 font-semibold">{{ __('Phone') }}</th>
                    <th scope="col" class="px-6 py-4 font-semibold">{{ __('Status') }}</th>
                    <th scope="col" class="px-6 py-4 font-semibold text-right">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($clients as $client)
                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors" wire:key="client-row-{{ $client->id }}">
                        <td class="px-6 py-4 font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $client->name }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $client->contact_person }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $client->email }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $client->phone }}
                        </td>
                        <td class="px-6 py-4">
                            @if($client->status === \App\Enums\ClientStatus::Active)
                                <flux:badge color="green" size="sm">{{ __('Active') }}</flux:badge>
                            @else
                                <flux:badge color="rose" size="sm">{{ __('Inactive') }}</flux:badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <flux:button 
                                wire:click="toggleStatus({{ $client->id }})" 
                                size="sm" 
                                variant="subtle" 
                                color="{{ $client->status === \App\Enums\ClientStatus::Active ? 'danger' : 'success' }}"
                            >
                                {{ $client->status === \App\Enums\ClientStatus::Active ? __('Deactivate') : __('Activate') }}
                            </flux:button>
                            <flux:button 
                                href="{{ route('clients.edit', $client) }}" 
                                size="sm" 
                                variant="subtle" 
                                icon="pencil" 
                                wire:navigate 
                                :label="__('Edit')"
                            />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-zinc-400 dark:text-zinc-500">
                            <div class="flex flex-col items-center justify-center space-y-2">
                                <flux:icon icon="building-office" class="size-8 text-zinc-300 dark:text-zinc-600" />
                                <span class="text-sm">{{ __('No clients found matching the filters.') }}</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pt-4">
        {{ $clients->links() }}
    </div>
</div>
