<flux:dropdown position="bottom" align="end">
    <button class="header-user shadow-sm shadow-emerald-500/20">
        <flux:avatar circle :name="auth()->user()->full_name" color="green"
            :src="auth()->user()->avatar ? asset('storage/' . auth()->user()->avatar) : null" size="xs"
            class="shrink-0" />
        <div class="text-left">
            <div class="header-user-name">{{ Str::limit(auth()->user()->full_name, 10) }}</div>
            <div class="header-user-role text-emerald-400">{{ __(auth()->user()->role?->role_name ?? '') }}</div>
        </div>

        {{-- Chevron --}}
        <svg class="size-3 shrink-0 text-slate-400 dark:text-zinc-500 transition-transform duration-200 group-aria-expanded:rotate-180"
            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="m6 9 6 6 6-6" />
        </svg>
    </button>

    <flux:menu class="rounded-xl">
        {{-- Header: info user --}}
        <div class="flex items-center gap-2 px-1 py-1.5 rounded-xl">
            <flux:avatar :name="auth()->user()->full_name" size="xs"
                :src="auth()->user()->avatar ? asset('storage/' . auth()->user()->avatar) : null" />
            <div class="flex flex-col min-w-0 flex-1">
                <span
                    class="font-heading text-[10px] font-semibold text-on-surface truncate dark:text-zinc-100">{{ Str::limit(auth()->user()->full_name, 20) }}</span>
                <span class="font-body text-[9px] text-emerald-600 font-bold dark:text-emerald-300 truncate">NIDN:
                    {{ auth()->user()->nidn ?? '...' }}</span>
            </div>
        </div>

        <flux:menu.separator />

        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
            {{ __('Settings') }}
        </flux:menu.item>

        {{-- NEW: Switch Account — only for SUPERADMIN, and only when NOT impersonating --}}
        @can('superadminOrAdmin')
            @if (! session('impersonator_id'))
                <flux:menu.item
                    icon="arrow-path"
                    x-on:click="$dispatch('open-switch-account')"
                    class="cursor-pointer">
                    {{ __('Switch Account') }}
                </flux:menu.item>
            @endif
        @endcan
            {{-- NEW: Return to my account — only when impersonating --}}
            @if (session('impersonator_id'))
                <flux:menu.item
                    icon="arrow-uturn-left"
                    x-on:click="$dispatch('return-to-admin')"
                    class="cursor-pointer text-amber-600 dark:text-amber-400">
                    {{ __('Return to my account') }}
                </flux:menu.item>
            @endif

        <flux:menu.separator />

        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                class="w-full cursor-pointer" data-test="logout-button">
                {{ __('Log out') }}
            </flux:menu.item>
        </form>
    </flux:menu>
</flux:dropdown>
