@props([
    'width' => 'sm:max-w-md',
])

<div
    x-data="{
        show: false,
        title: 'Delete Item?',
        message: 'You are about to delete:',
        subject: '',
        note: 'This action cannot be undone.',
        confirmLabel: 'Delete',
        cancelLabel: 'Cancel',
        action: '',
        payload: {},

        open(detail = {}) {
            this.title        = detail.title        ?? 'Delete Item?';
            this.message      = detail.message      ?? 'You are about to delete:';
            this.subject      = detail.subject      ?? '';
            this.note         = detail.note         ?? 'This action cannot be undone.';
            this.confirmLabel = detail.confirmLabel ?? 'Delete';
            this.cancelLabel  = detail.cancelLabel  ?? 'Cancel';
            this.action       = detail.action       ?? '';
            this.payload      = detail.payload      ?? {};
            this.show         = true;
        },

        close() {
            this.show = false;
        },

        confirm() {
            // Dispatch ke Livewire global event bus
            Livewire.dispatch('delete-confirmed', {
                action: this.action,
                payload: this.payload,
            });
            this.close();
        }
    }"
    x-on:confirm-delete.window="open($event.detail)"
    x-on:keydown.escape.window="close()"
    x-show="show"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-cloak
    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center
           p-0 sm:p-4 bg-inverse-surface/40 backdrop-blur-sm">

    <div
        x-on:click.away="close()"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="bg-surface-container-lowest dark:bg-zinc-900 shadow-lg
               w-full {{ $width }}
               rounded-t-2xl sm:rounded-xl
               p-5 sm:p-6
               border border-outline-variant/50 dark:border-zinc-700">

        <div class="flex items-start gap-4">
            <div
                class="shrink-0 w-10 h-10 rounded-full
                       bg-error-container text-error
                       flex items-center justify-center
                       dark:bg-rose-900/40 dark:text-rose-400">
                <flux:icon.exclamation-triangle class="size-5" />
            </div>

            <div class="flex-1 min-w-0">
                <h3 class="font-heading text-base font-semibold
                           text-on-surface dark:text-zinc-100"
                    x-text="title"></h3>

                <p x-show="message"
                   class="mt-1 text-sm text-on-surface-variant dark:text-zinc-400"
                   x-text="message"></p>

                <p x-show="subject"
                   class="mt-1 text-sm font-medium line-clamp-2
                          text-on-surface dark:text-zinc-100">
                    "<span x-text="subject"></span>"
                </p>

                <p x-show="note"
                   class="mt-2 text-xs text-outline dark:text-zinc-500"
                   x-text="note"></p>
            </div>
        </div>

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 mt-6">
            <button type="button"
                x-on:click="close()"
                class="w-full sm:w-auto px-4 py-2 text-sm font-medium rounded-md
                       text-on-surface-variant dark:text-zinc-300
                       bg-surface-container-lowest dark:bg-zinc-800
                       border border-outline-variant dark:border-zinc-600
                       hover:bg-surface-container-low dark:hover:bg-zinc-700
                       focus:outline-none focus:ring-2 focus:ring-primary
                       transition-colors duration-150"
                x-text="cancelLabel"></button>

            <button type="button"
                x-on:click="confirm()"
                class="w-full sm:w-auto px-4 py-2 text-sm font-medium
                       text-on-error rounded-md
                       bg-error hover:bg-error/90
                       focus:outline-none focus:ring-2 focus:ring-error
                       transition-colors duration-150"
                x-text="confirmLabel"></button>
        </div>
    </div>
</div>
