@if (session('impersonator_id'))
    @php
        $original = \App\Models\User::find(session('impersonator_id'));
    @endphp
    @if ($original)
        <div class="mt-2 mx-2 sm:mx-4 rounded-2xl sm:rounded-full sticky top-2 z-30
                    bg-amber-50/95 dark:bg-amber-950/40 backdrop-blur-md
                    border border-amber-300/80 dark:border-amber-700/60
                    shadow-sm shadow-amber-500/10 dark:shadow-amber-950/40">
            <div class="max-w-7xl mx-auto px-3 sm:px-4 py-2 sm:py-1.5
                        flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-3">

                {{-- Info --}}
                <div class="flex items-center gap-2 min-w-0">
                    {{-- Warning icon --}}
                    <span class="relative flex shrink-0 w-5 h-5 items-center justify-center
                                 rounded-full bg-amber-200 dark:bg-amber-900/60">
                        <span class="absolute inset-0 rounded-full bg-amber-400/40 animate-ping"></span>
                        <flux:icon.exclamation-triangle class="relative size-3 text-amber-700 dark:text-amber-300" />
                    </span>

                    {{-- Text --}}
                    <p class="text-[11px] sm:text-[11.5px] font-medium min-w-0
                              text-amber-900/80 dark:text-amber-100/80">
                        <span class="font-semibold text-amber-800 dark:text-amber-200">
                            {{ __('Warning:') }}
                        </span>
                        <span class="hidden sm:inline">{{ __('You are signed in as') }}</span>
                        <span class="sm:hidden">{{ __('Viewing as') }}</span>
                        <strong class="text-amber-900 dark:text-amber-100">
                            {{ auth()->user()->full_name }}
                        </strong>
                        <span class="hidden lg:inline text-amber-700 dark:text-amber-300">
                            {{ __('— actions will be attributed to this account.') }}
                        </span>
                    </p>
                </div>

                {{-- Return button --}}
                <button type="button" x-data
                    x-on:click="$dispatch('return-to-admin')"
                    class="shrink-0 inline-flex items-center justify-center gap-1.5
                           w-full sm:w-auto px-3 py-1.5 sm:py-1 rounded-full
                           text-[11px] font-semibold
                           text-white
                           bg-amber-600 hover:bg-amber-700
                           dark:bg-amber-700 dark:hover:bg-amber-600
                           shadow-sm shadow-amber-500/30
                           active:scale-[0.97] transition-all">
                    <flux:icon.arrow-uturn-left class="size-3" />
                    <span class="hidden sm:inline">{{ __('Return to') }} {{ Str::limit($original->full_name, 20) }}</span>
                    <span class="sm:hidden">{{ __('Return to my account') }}</span>
                </button>
            </div>
        </div>
    @endif
@endif
