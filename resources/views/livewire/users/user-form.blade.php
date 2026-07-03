<div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ $isEdit ? __('Edit User') : __('Create User') }}</flux:heading>
            <flux:subheading>{{ $isEdit ? __('Update user profile information, role permissions, and active status.') : __('Add a new user account to the system with scoped role permissions.') }}</flux:subheading>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl p-6 shadow-sm">
        <form wire:submit="save" class="space-y-6">
            <!-- Full Name -->
            <flux:field>
                <flux:label>{{ __('Full Name') }}</flux:label>
                <flux:input wire:model="name" placeholder="{{ __('John Doe') }}" required />
                <flux:error name="name" />
            </flux:field>

            <!-- Email Address -->
            <flux:field>
                <flux:label>{{ __('Email Address') }}</flux:label>
                <flux:input type="email" wire:model="email" placeholder="{{ __('john@example.com') }}" required />
                <flux:error name="email" />
            </flux:field>

            <!-- Role Select -->
            <flux:field>
                <flux:label>{{ __('Role') }}</flux:label>
                <flux:select wire:model.live="role">
                    <option value="client">{{ __('Client') }}</option>
                    <option value="engineer">{{ __('Engineer') }}</option>
                    <option value="admin">{{ __('Admin') }}</option>
                </flux:select>
                <flux:error name="role" />
            </flux:field>

            <!-- Conditional Client Association -->
            @if ($role === 'client')
                <flux:field>
                    <flux:label>{{ __('Client Association') }}</flux:label>
                    <flux:select wire:model="client_id" placeholder="{{ __('Select associated client company...') }}">
                        @foreach ($clients as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="client_id" />
                </flux:field>
            @endif

            <!-- Status (Active / Inactive) -->
            <flux:field>
                <flux:label>{{ __('Status') }}</flux:label>
                <flux:select wire:model="is_active">
                    <option value="1">{{ __('Active') }}</option>
                    <option value="0">{{ __('Inactive') }}</option>
                </flux:select>
                <flux:error name="is_active" />
            </flux:field>

            <!-- Password Input -->
            <flux:field>
                <flux:label>{{ __('Password') }}</flux:label>
                <flux:input type="password" wire:model="password" viewable :required="!$isEdit" />
                @if ($isEdit)
                    <flux:text variant="subtle" class="text-xs mt-1 block">
                        {{ __('Leave blank to keep the current password.') }}
                    </flux:text>
                @endif
                <flux:error name="password" />
            </flux:field>

            <!-- Password Confirmation -->
            <flux:field>
                <flux:label>{{ __('Confirm Password') }}</flux:label>
                <flux:input type="password" wire:model="password_confirmation" viewable :required="!$isEdit" />
                <flux:error name="password_confirmation" />
            </flux:field>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                <flux:button href="{{ route('users.index') }}" variant="subtle" wire:navigate>
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button type="submit" variant="primary">
                    {{ $isEdit ? __('Save Changes') : __('Create User') }}
                </flux:button>
            </div>
        </form>
    </div>
</div>
