<div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ $isEdit ? __('Edit Client') : __('Create Client') }}</flux:heading>
            <flux:subheading>{{ $isEdit ? __('Update the information for this client company.') : __('Add a new client company to the system.') }}</flux:subheading>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl p-6 shadow-sm">
        <form wire:submit="save" class="space-y-6">
            <!-- Client Name -->
            <flux:field>
                <flux:label>{{ __('Client Name') }}</flux:label>
                <flux:input wire:model="name" placeholder="{{ __('Acme Corporation') }}" required />
                <flux:error name="name" />
            </flux:field>

            <!-- Contact Person -->
            <flux:field>
                <flux:label>{{ __('Contact Person') }}</flux:label>
                <flux:input wire:model="contact_person" placeholder="{{ __('John Doe') }}" required />
                <flux:error name="contact_person" />
            </flux:field>

            <!-- Email -->
            <flux:field>
                <flux:label>{{ __('Email Address') }}</flux:label>
                <flux:input type="email" wire:model="email" placeholder="{{ __('john@acme.com') }}" required />
                <flux:error name="email" />
            </flux:field>

            <!-- Phone -->
            <flux:field>
                <flux:label>{{ __('Phone Number') }}</flux:label>
                <flux:input wire:model="phone" placeholder="{{ __('+1 (555) 000-0000') }}" required />
                <flux:error name="phone" />
            </flux:field>

            <!-- Status -->
            <flux:field>
                <flux:label>{{ __('Status') }}</flux:label>
                <flux:select wire:model="status">
                    <option value="active">{{ __('Active') }}</option>
                    <option value="inactive">{{ __('Inactive') }}</option>
                </flux:select>
                <flux:error name="status" />
            </flux:field>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                <flux:button href="{{ route('clients.index') }}" variant="subtle" wire:navigate>
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button type="submit" variant="primary">
                    {{ $isEdit ? __('Save Changes') : __('Create Client') }}
                </flux:button>
            </div>
        </form>
    </div>
</div>
