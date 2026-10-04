<flux:dropdown position="bottom" align="end">
    <button class="header-user shadow-sm shadow-emerald-500/20">
        <flux:avatar circle :name="auth()->user()->full_name" color="green"
            :src="auth()->user()->avatar ? asset('storage/' . auth()->user()->avatar) : null" size="xs"
            class="shrink-0" />
        <div class="text-left">
            <div class="header-user-name">{{ auth()->user()->full_name }}</div>
            <div class="header-user-role text-emerald-400">{{ __(auth()->user()->role?->role_name ?? '') }}</div>
        </div>
    </button>

    <flux:menu class="rounded-xl">
        {{-- Header: info user --}}
        <div class="flex items-center gap-2 px-1 py-1.5 rounded-xl">
            <flux:avatar :name="auth()->user()->full_name" size="xs"
                :src="auth()->user()->avatar ? asset('storage/' . auth()->user()->avatar) : null" />
            <div class="flex flex-col min-w-0 flex-1">
                <span
                    class="font-heading text-[10px] font-semibold text-on-surface truncate dark:text-zinc-100">{{ auth()->user()->full_name }}</span>
                <span class="font-body text-[9px]  text-emerald-600 font-bold dark:text-emerald-300 truncate">NIDN:
                    {{ auth()->user()->nidn ?? '...' }}</span>
            </div>
        </div>

        <flux:menu.separator />

        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
            {{ __('Settings') }}
        </flux:menu.item>

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
