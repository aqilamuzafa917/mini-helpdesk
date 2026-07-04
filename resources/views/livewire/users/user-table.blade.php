<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Users') }}</flux:heading>
            <flux:subheading>{{ __('Manage system users, define their roles, associate clients, and manage active status.') }}</flux:subheading>
        </div>
        <flux:button href="{{ route('users.create') }}" variant="primary" icon="plus" wire:navigate>
            {{ __('Add User') }}
        </flux:button>
    </div>

    <!-- Filters -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center bg-zinc-50 dark:bg-zinc-900/50 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700">
        <!-- Search -->
        <div class="flex-1">
            <flux:input 
                wire:model.live.debounce.300ms="search" 
                placeholder="{{ __('Search users by name or email...') }}" 
                icon="magnifying-glass" 
                clearable 
            />
        </div>

        <!-- Role Filter -->
        <div class="flex items-center gap-2">
            <flux:text class="text-sm font-medium whitespace-nowrap">{{ __('Role:') }}</flux:text>
            <select 
                wire:model.live="roleFilter" 
                class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-800 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:focus:border-zinc-500"
            >
                <option value="">{{ __('All Roles') }}</option>
                <option value="admin">{{ __('Admin') }}</option>
                <option value="engineer">{{ __('Engineer') }}</option>
                <option value="client">{{ __('Client') }}</option>
            </select>
        </div>

        <!-- Status Filter -->
        <div class="flex items-center gap-2">
            <flux:text class="text-sm font-medium whitespace-nowrap">{{ __('Status:') }}</flux:text>
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

    <!-- Notifications / Session Flash -->
    @if (session()->has('status'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-800 dark:text-emerald-300 rounded-lg border border-emerald-200 dark:border-emerald-800 flex items-center justify-between">
            <span class="text-sm">{{ session('status') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="p-4 bg-rose-50 dark:bg-rose-950/30 text-rose-800 dark:text-rose-300 rounded-lg border border-rose-200 dark:border-rose-800 flex items-center justify-between">
            <span class="text-sm">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Table -->
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
        <table class="w-full text-left text-sm text-zinc-500 dark:text-zinc-400">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-xs uppercase text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700">
                <tr>
                    <th scope="col" class="px-6 py-4 font-semibold">{{ __('User Name') }}</th>
                    <th scope="col" class="px-6 py-4 font-semibold">{{ __('Email') }}</th>
                    <th scope="col" class="px-6 py-4 font-semibold">{{ __('Role') }}</th>
                    <th scope="col" class="px-6 py-4 font-semibold">{{ __('Client Association') }}</th>
                    <th scope="col" class="px-6 py-4 font-semibold">{{ __('Status') }}</th>
                    <th scope="col" class="px-6 py-4 font-semibold text-right">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($users as $user)
                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors" wire:key="user-row-{{ $user->id }}">
                        <td class="px-6 py-4 font-medium text-zinc-900 dark:text-zinc-100">
                            <div class="flex items-center gap-3">
                                <flux:avatar :name="$user->name" :initials="$user->initials()" size="sm" />
                                <div>
                                    <span class="block font-medium">{{ $user->name }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            {{ $user->email }}
                        </td>
                        <td class="px-6 py-4">
                            @if($user->role === \App\Enums\Role::Admin)
                                <flux:badge color="purple" size="sm">{{ __('Admin') }}</flux:badge>
                            @elseif($user->role === \App\Enums\Role::Engineer)
                                <flux:badge color="blue" size="sm">{{ __('Engineer') }}</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">{{ __('Client') }}</flux:badge>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($user->role === \App\Enums\Role::Client && $user->client)
                                <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $user->client->name }}</span>
                            @else
                                <span class="text-zinc-400 dark:text-zinc-600">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($user->is_active)
                                <flux:badge color="green" size="sm">{{ __('Active') }}</flux:badge>
                            @else
                                <flux:badge color="rose" size="sm">{{ __('Inactive') }}</flux:badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <flux:button 
                                wire:click="toggleStatus({{ $user->id }})" 
                                size="sm" 
                                variant="subtle" 
                                color="{{ $user->is_active ? 'danger' : 'success' }}"
                                :disabled="auth()->id() === $user->id"
                            >
                                {{ $user->is_active ? __('Deactivate') : __('Activate') }}
                            </flux:button>
                            <flux:button 
                                href="{{ route('users.edit', $user) }}" 
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
                                <flux:icon icon="users" class="size-8 text-zinc-300 dark:text-zinc-600" />
                                <span class="text-sm">{{ __('No users found matching the filters.') }}</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pt-4">
        {{ $users->links() }}
    </div>
</div>
