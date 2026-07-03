<div class="space-y-6">
    <flux:heading size="lg" level="2" class="font-semibold text-zinc-900 dark:text-zinc-50 flex items-center gap-2 border-b border-zinc-100 dark:border-zinc-800 pb-3">
        <flux:icon icon="chat-bubble-left-right" class="size-5 text-zinc-500" />
        {{ __('Comments') }}
        <span class="text-xs bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 px-2 py-0.5 rounded-full font-mono">
            {{ $comments->count() }}
        </span>
    </flux:heading>

    <!-- Comments List -->
    <div class="space-y-4 max-h-[500px] overflow-y-auto pr-2">
        @forelse ($comments as $commentItem)
            <div class="p-4 rounded-xl border {{ $commentItem->is_internal ? 'border-amber-200 bg-amber-50/40 dark:border-amber-900/30 dark:bg-amber-950/10' : 'border-zinc-150 bg-zinc-50/50 dark:border-zinc-800 dark:bg-zinc-900/30' }} flex gap-4 text-sm" wire:key="comment-{{ $commentItem->id }}">
                
                <!-- Avatar -->
                <flux:avatar :name="$commentItem->user->name" :initials="$commentItem->user->initials()" size="sm" class="flex-shrink-0" />
                
                <!-- Body -->
                <div class="flex-1 space-y-1">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $commentItem->user->name }}</span>
                            
                            <!-- Role Badge -->
                            @if ($commentItem->user->role === \App\Enums\Role::Admin)
                                <flux:badge color="purple" size="sm" class="scale-90 transform origin-left">{{ __('Admin') }}</flux:badge>
                            @elseif ($commentItem->user->role === \App\Enums\Role::Engineer)
                                <flux:badge color="blue" size="sm" class="scale-90 transform origin-left">{{ __('Engineer') }}</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm" class="scale-90 transform origin-left">{{ __('Client') }}</flux:badge>
                            @endif

                            @if ($commentItem->is_internal)
                                <flux:badge color="amber" size="sm" class="scale-90 transform origin-left">{{ __('Internal') }}</flux:badge>
                            @endif
                        </div>
                        <span class="text-xs text-zinc-400 dark:text-zinc-500">
                            {{ $commentItem->created_at->diffForHumans() }}
                        </span>
                    </div>
                    <div class="text-zinc-700 dark:text-zinc-300 text-sm whitespace-pre-line leading-relaxed">
                        {{ $commentItem->comment }}
                    </div>
                </div>

            </div>
        @empty
            <div class="text-center py-8 text-zinc-400 dark:text-zinc-600 text-sm italic">
                {{ __('No comments posted yet.') }}
            </div>
        @endforelse
    </div>

    <!-- Submit Comment Form -->
    <div class="pt-4 border-t border-zinc-100 dark:border-zinc-800">
        @if (session()->has('comment_status'))
            <div class="p-3 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-800 dark:text-emerald-300 rounded-lg border border-emerald-100 dark:border-emerald-900 text-xs mb-4">
                {{ session('comment_status') }}
            </div>
        @endif

        <form wire:submit="addComment" class="space-y-4">
            <flux:field>
                <flux:textarea 
                    wire:model="comment" 
                    placeholder="{{ __('Type your comment here...') }}" 
                    rows="3" 
                    required 
                />
                <flux:error name="comment" />
            </flux:field>

            <div class="flex items-center justify-between flex-wrap gap-4">
                <!-- Internal Comment Checkbox (Admin & Engineer only) -->
                @if (auth()->user()->role === \App\Enums\Role::Admin || auth()->user()->role === \App\Enums\Role::Engineer)
                    <flux:checkbox 
                        wire:model="is_internal" 
                        label="{{ __('Internal Comment (visible only to support team)') }}" 
                    />
                @else
                    <div></div>
                @endif

                <flux:button type="submit" variant="primary" size="sm">
                    {{ __('Post Comment') }}
                </flux:button>
            </div>
        </form>
    </div>
</div>
