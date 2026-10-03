@props([
    'width' => 'sm:max-w-xs',
])

<div
    x-data="{
        show: false,
        title: 'Hapus Item?',
        message: 'Anda akan menghapus:',
        subject: '',
        note: '',
        confirmLabel: 'Hapus',
        cancelLabel: 'Batal',
        action: '',
        payload: {},
        loading: false,

        open(detail = {}) {
            this.title        = detail.title        ?? 'Hapus Item?';
            this.message      = detail.message      ?? 'Anda akan menghapus:';
            this.subject      = detail.subject      ?? '';
            this.note         = detail.note         ?? '';
            this.confirmLabel = detail.confirmLabel ?? 'Hapus';
            this.cancelLabel  = detail.cancelLabel  ?? 'Batal';
            this.action       = detail.action       ?? '';
            this.payload      = detail.payload      ?? {};
            this.loading      = false;
            this.show         = true;
        },

        close() {
            if (this.loading) return;
            this.show = false;
        },

        confirm() {
            if (! this.action) return;
            this.loading = true;

            Livewire.dispatch('delete-confirmed', {
                action: this.action,
                payload: this.payload,
            });

            Livewire.hook('morph.updated', () => {
                this.loading = false;
                this.show = false;
            });
        }
    }"
    x-on:confirm-delete.window="open($event.detail)"
    x-on:keydown.escape.window="close()"
    x-show="show"
    x-transition:enter="transition ease-out duration-150"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-cloak
    role="dialog"
    aria-modal="true"
    class="fixed inset-0 z-[60] flex items-center justify-center
           p-4 bg-black/40 backdrop-blur-sm">

    <div
        x-on:click.away="close()"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="bg-white dark:bg-zinc-900 shadow-xl
               w-full {{ $width }}
               rounded-lg
               p-4
               border border-zinc-200 dark:border-zinc-700">

        {{-- Icon + Content --}}
        <div class="flex items-start gap-3">
            <div class="shrink-0 w-8 h-8 rounded-full
                        bg-rose-100 text-rose-600
                        flex items-center justify-center
                        dark:bg-rose-900/40 dark:text-rose-400">
                <flux:icon.exclamation-triangle class="size-4" />
            </div>

            <div class="flex-1 min-w-0">
                <h3 class="text-sm font-semibold leading-tight
                           text-zinc-900 dark:text-zinc-100"
                    x-text="title"></h3>

                <p x-show="message"
                   class="mt-1 text-xs text-zinc-500 dark:text-zinc-400"
                   x-text="message"></p>

                <p x-show="subject"
                   class="mt-0.5 text-xs font-medium line-clamp-2
                          text-zinc-700 dark:text-zinc-300">
                    "<span x-text="subject"></span>"
                </p>

                <p x-show="note"
                   class="mt-1.5 text-[11px] text-zinc-400 dark:text-zinc-500"
                   x-text="note"></p>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end gap-2 mt-4">
            <button type="button"
                x-on:click="close()"
                x-bind:disabled="loading"
                class="px-3 py-1.5 text-xs font-medium rounded-md
                       text-zinc-600 dark:text-zinc-300
                       bg-white dark:bg-zinc-800
                       border border-zinc-300 dark:border-zinc-600
                       hover:bg-zinc-50 dark:hover:bg-zinc-700
                       disabled:opacity-50 disabled:cursor-not-allowed
                       transition-colors"
                x-text="cancelLabel"></button>

            <button type="button"
                x-on:click="confirm()"
                x-bind:disabled="loading"
                class="inline-flex items-center justify-center gap-1.5
                       px-3 py-1.5 text-xs font-medium rounded-md
                       text-white
                       bg-rose-600 hover:bg-rose-700
                       disabled:opacity-50 disabled:cursor-wait
                       transition-colors">
                <svg x-show="loading" class="animate-spin size-3" viewBox="0 0 24 24" fill="none"
                     xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/>
                    <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                </svg>
                <span x-text="confirmLabel"></span>
            </button>
        </div>
    </div>
</div>
