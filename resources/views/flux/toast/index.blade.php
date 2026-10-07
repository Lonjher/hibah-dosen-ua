@blaze(fold: true, safe: ['position'])

@props([
    'position' => 'top right',
])

<ui-toast x-data x-on:toast-show.document="! $el.closest('ui-toast-group') && $el.showToast($event.detail)" popover="manual" position="{{ $position }}" wire:ignore>
    <template>
        <div {{ $attributes->only(['class'])->class('w-fit max-w-xs') }} data-variant="" data-flux-toast-dialog>
            <div class="px-3 py-1.5 flex items-center gap-2 rounded-full shadow-lg bg-white border border-slate-200 border-b-slate-300/80 dark:bg-zinc-900 dark:border-zinc-800 overflow-hidden">
                {{-- Success icon --}}
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="hidden [[data-flux-toast-dialog][data-variant=success]_&]:block shrink-0 size-3.5 text-emerald-600 dark:text-emerald-400">
                    <path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14Zm3.844-8.791a.75.75 0 0 0-1.188-.918l-3.7 4.79-1.649-1.833a.75.75 0 1 0-1.114 1.004l2.25 2.5a.75.75 0 0 0 1.15-.043l4.25-5.5Z" clip-rule="evenodd" />
                </svg>

                {{-- Warning icon --}}
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="hidden [[data-flux-toast-dialog][data-variant=warning]_&]:block shrink-0 size-3.5 text-amber-500 dark:text-amber-400">
                    <path fill-rule="evenodd" d="M6.701 2.25c.577-1 2.02-1 2.598 0l5.196 9a1.5 1.5 0 0 1-1.299 2.25H2.804a1.5 1.5 0 0 1-1.3-2.25l5.197-9ZM8 4a.75.75 0 0 1 .75.75v3a.75.75 0 1 1-1.5 0v-3A.75.75 0 0 1 8 4Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                </svg>

                {{-- Info icon --}}
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="hidden [[data-flux-toast-dialog][data-variant=info]_&]:block shrink-0 size-3.5 text-cyan-500 dark:text-cyan-400">
                    <path fill-rule="evenodd" d="M15 8A7 7 0 1 1 1 8a7 7 0 0 1 14 0ZM9 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM6.75 8a.75.75 0 0 0 0 1.5h.75v1.75a.75.75 0 0 0 1.5 0v-2.5A.75.75 0 0 0 8.25 8h-1.5Z" clip-rule="evenodd" />
                </svg>

                {{-- Danger icon --}}
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="hidden [[data-flux-toast-dialog][data-variant=danger]_&]:block shrink-0 size-3.5 text-rose-500 dark:text-rose-400">
                    <path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14ZM8 4a.75.75 0 0 1 .75.75v3a.75.75 0 0 1-1.5 0v-3A.75.75 0 0 1 8 4Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                </svg>

                {{-- Heading + Text --}}
                <div class="min-w-0 flex items-center gap-1.5 text-xs truncate">
                    <span class="font-medium text-slate-800 dark:text-zinc-100 truncate"><slot name="heading"></slot></span>
                    <span class="font-normal text-slate-500 dark:text-zinc-400 truncate"><slot name="text"></slot></span>

                    {{-- Link --}}
                    <template name="link">
                        <a class="shrink-0 font-medium text-[11px] text-emerald-600 dark:text-emerald-400 decoration-emerald-300 dark:decoration-emerald-700 underline underline-offset-[6px] hover:decoration-current"><slot name="text"></slot></a>
                    </template>
                </div>

                {{-- Close button --}}
                <ui-close class="shrink-0 flex items-center">
                    <button type="button" class="inline-flex items-center justify-center disabled:opacity-50 dark:disabled:opacity-75 disabled:cursor-default size-5 rounded-full bg-transparent hover:bg-slate-800/5 dark:hover:bg-white/15 text-slate-400 hover:text-slate-800 dark:text-zinc-500 dark:hover:text-white transition-colors" as="button">
                        <svg class="size-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"></path>
                        </svg>
                    </button>
                </ui-close>
            </div>
        </div>
    </template>
</ui-toast>
