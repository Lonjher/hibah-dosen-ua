<?php

use App\Models\User;
use Livewire\Component;

new class extends Component {
    /**
     * Fetch user data on demand. Returns null kalau user tidak ada
     * atau tidak punya avatar → modal tidak akan terbuka.
     */
    public function open(int $id): ?array
    {
        $user = User::query()->select('id', 'full_name', 'avatar')->find($id);

        if (!$user || !$user->avatar) {
            return null;
        }
        $url = asset('storage/' . $user->avatar);

        return [
            'url' => $url,
            'name' => $user->full_name,
        ];
    }
}; ?>

<div x-data="{
    show: false,
    url: null,
    name: '',

    init() {
        window.addEventListener('open-avatar', (e) => this.handleOpen(e));
    },

    async handleOpen(e) {
        const data = await $wire.open(e.detail.id);
        if (!data) return;
        this.url = data.url;
        this.name = data.name;
        this.show = true;
    },

    close() {
        this.show = false;
        setTimeout(() => {
            this.url = null;
            this.name = '';
        }, 200);
    }
}" x-show="show" x-transition.opacity x-cloak x-on:keydown.escape.window="close()"
    class="fixed inset-0 z-[100] flex items-center justify-center p-4" @click.self="close()">

    <div x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        class="relative flex flex-col items-center
           bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl
           border border-slate-200 dark:border-zinc-700"
        @click.stop>

        {{-- ══════════ CLOSE (top-right) ══════════ --}}
        <button type="button" @click="close()" aria-label="Close"
            class="absolute top-2.5 right-2.5 w-7 h-7 rounded-full flex items-center justify-center
               text-slate-500 dark:text-zinc-400
               hover:text-white hover:bg-emerald-600
               dark:hover:text-white dark:hover:bg-emerald-600
               transition-all duration-150
               hover:scale-110 active:scale-95">
            <flux:icon.x-mark class="size-3.5" />
        </button>

        {{-- ══════════ AVATAR (circle) ══════════ --}}
        <template x-if="url">
            <img :src="url" :alt="name"
                class="w-44 h-44 sm:w-52 sm:h-52
                   rounded-full object-cover
                   shadow-2xl shadow-slate-900/20 dark:shadow-black/60" />
        </template>

        {{-- ══════════ DOWNLOAD (bottom-right) ══════════ --}}
        <a :href="url" download aria-label="Download"
            class="absolute bottom-2.5 right-2.5 w-7 h-7 rounded-full flex items-center justify-center
               text-slate-500 dark:text-zinc-400
               hover:text-white hover:bg-emerald-600
               dark:hover:text-white dark:hover:bg-emerald-600
               transition-all duration-150
               hover:scale-110 active:scale-95">
            <flux:icon.arrow-down-tray class="size-3.5" />
        </a>
    </div>
</div>
