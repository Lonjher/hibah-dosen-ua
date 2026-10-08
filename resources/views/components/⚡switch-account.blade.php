<?php

use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public bool $show = false;
    public string $search = '';

    #[On('open-switch-account')]
    public function open(): void
    {
        $this->guard();
        $this->reset(['search']);
        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
        $this->reset(['search']);
    }

    public function switchTo(int $userId)
    {
        $this->guard();

        $target = User::find($userId);

        if (!$target) {
            Flux::toast('User not found.', variant: 'danger');
            return;
        }

        if ($target->id === auth()->id()) {
            Flux::toast('You are already signed in as this account.', variant: 'warning');
            return;
        }

        session(['impersonator_id' => auth()->id()]);

        Auth::login($target);

        Flux::toast("Switched to {$target->full_name}.", variant: 'success');

        return redirect()->route('dashboard');
    }

    #[On('return-to-admin')]
    public function returnToAdmin()
    {
        $originalId = session('impersonator_id');

        if (!$originalId) {
            Flux::toast('No impersonation session found.', variant: 'danger');
            return;
        }

        $original = User::find($originalId);

        if (!$original) {
            session()->forget('impersonator_id');
            Flux::toast('Original account not found.', variant: 'danger');
            return;
        }

        Auth::login($original);
        session()->forget('impersonator_id');

        Flux::toast("Returned to {$original->full_name}.", variant: 'success');

        return redirect()->route('dashboard');
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->when($this->search !== '', function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('full_name', 'like', $term)->orWhere('nidn', 'like', $term)->orWhere('email', 'like', $term);
                });
            })
            ->where('id', '!=', auth()->id())
            ->whereHas('role', function ($q) {
                $q->where('role_code', '!=', 'SUPERADMIN');
            })
            ->with('role')
            ->orderBy('full_name')
            ->limit(5)
            ->get();
    }

    protected function guard(): void
    {
        Gate::authorize('switch-account');
    }
};
?>

<div x-data x-show="$wire.show" x-transition.opacity x-cloak x-on:keydown.escape.window="$wire.close()"
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4 font-sans"
    @click.self="$wire.close()">

    <div class="flex max-h-[92vh] w-full sm:max-w-md flex-col overflow-hidden
                bg-white dark:bg-zinc-900 rounded-t-3xl sm:rounded-3xl shadow-2xl
                border border-slate-200 dark:border-zinc-700"
        @click.stop>

        {{-- HEADER --}}
        <div class="shrink-0 bg-gradient-to-r from-amber-600 to-amber-500 px-4 py-2.5">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-7 h-7 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                        <flux:icon.arrows-right-left class="size-3.5 text-white" />
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-[12.5px] font-semibold text-white leading-tight">
                            {{ __('Switch Account') }}
                        </h3>
                        <p class="text-[10px] text-white/75 leading-tight">
                            {{ __('Sign in as another user') }}
                        </p>
                    </div>
                </div>
                <button type="button" wire:click="close" aria-label="Close"
                    class="shrink-0 w-6 h-6 rounded-full flex items-center justify-center
                           text-white/80 hover:text-white hover:bg-white/10 transition-colors">
                    <flux:icon.x-mark class="size-3" />
                </button>
            </div>
        </div>

        {{-- BODY --}}
        <div class="flex-1 overflow-y-auto px-4 py-3 space-y-2.5">

            {{-- Search --}}
            <x-input-search wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Search by name, NIDN, or email…') }}" size="md" rounded="full"
                maxWidth="w-full" :responsive="false" />

            {{-- List --}}
            <div class="relative">
                {{-- Loading overlay saat search --}}
                <div wire:loading.delay.shortest wire:target="search"
                    class="absolute inset-0 z-10 flex items-start justify-center pt-4
               bg-white/60 dark:bg-zinc-900/60 backdrop-blur-[2px]
               rounded-2xl pointer-events-none
               transition-opacity duration-200">
                    <div
                        class="flex items-center gap-2 px-3 py-1.5 rounded-full
                    bg-white dark:bg-zinc-800
                    shadow-sm shadow-zinc-200/60 dark:shadow-zinc-950/40
                    border border-slate-200 dark:border-zinc-700">
                        <svg class="animate-spin size-3 text-amber-500" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                                opacity=".25" />
                            <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
                        </svg>
                        <span class="text-[10.5px] font-medium text-slate-600 dark:text-zinc-400">
                            {{ __('Searching…') }}
                        </span>
                    </div>
                </div>

                {{-- List container dengan stagger animation --}}
                <div class="space-y-1" wire:loading.class.delay="opacity-40" wire:target="search">
                    @forelse ($this->users as $index => $user)
                        <button type="button" wire:key="user-{{ $user->id }}"
                            wire:click="switchTo({{ $user->id }})" wire:loading.attr="disabled"
                            wire:target="switchTo({{ $user->id }})"
                            style="animation-delay: {{ min($index * 25, 250) }}ms"
                            class="animate-list-item w-full flex items-center gap-2 px-2.5 py-1.5 rounded-full text-left
                       bg-slate-50 dark:bg-zinc-800/40
                       border border-slate-200 dark:border-zinc-700/60
                       hover:bg-amber-50 dark:hover:bg-amber-900/15
                       hover:border-amber-300 dark:hover:border-amber-700/60
                       hover:scale-[1.01] active:scale-[0.99]
                       disabled:opacity-60 disabled:cursor-wait
                       transition-all duration-150 group">

                            {{-- Avatar --}}
                            @if ($user->avatar)
                                <div
                                    class="w-6 h-6 rounded-full shrink-0 overflow-hidden
                                ring-1 ring-white/40 dark:ring-zinc-800/40
                                group-hover:scale-105 transition-transform duration-200">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($user->avatar) }}"
                                        alt="{{ $user->full_name }}" loading="lazy"
                                        class="w-full h-full object-cover" />
                                </div>
                            @else
                                <div
                                    class="w-6 h-6 rounded-full shrink-0
                                bg-gradient-to-br from-emerald-100 to-teal-50
                                dark:from-emerald-900/30 dark:to-teal-900/20
                                flex items-center justify-center
                                text-[9px] font-bold
                                text-emerald-700 dark:text-emerald-300
                                ring-1 ring-white/40 dark:ring-zinc-800/40
                                group-hover:scale-105 transition-transform">
                                    {{ $user->initials() }}
                                </div>
                            @endif

                            {{-- Info --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1 min-w-0">
                                    <span
                                        class="text-[11.5px] font-medium truncate
                                     text-slate-800 dark:text-zinc-100">
                                        {{ $user->full_name }}
                                    </span>
                                    @php $code = $user->role?->role_code; @endphp
                                    @if ($code === 'ADMIN')
                                        <span
                                            class="shrink-0 px-1.5 py-px rounded-full
                                         bg-amber-100 dark:bg-amber-900/40
                                         text-amber-700 dark:text-amber-400
                                         text-[8.5px] font-semibold uppercase tracking-wide">
                                            Admin
                                        </span>
                                    @elseif ($code === 'REVIEWER')
                                        <span
                                            class="shrink-0 px-1.5 py-px rounded-full
                                         bg-sky-100 dark:bg-sky-900/40
                                         text-sky-700 dark:text-sky-400
                                         text-[8.5px] font-semibold uppercase tracking-wide">
                                            Reviewer
                                        </span>
                                    @else
                                        <span
                                            class="shrink-0 px-1.5 py-px rounded-full
                                         bg-slate-100 dark:bg-zinc-800
                                         text-slate-600 dark:text-zinc-400
                                         text-[8.5px] font-semibold uppercase tracking-wide">
                                            User
                                        </span>
                                    @endif
                                </div>
                                <div
                                    class="flex items-center gap-1.5 text-[9.5px]
                                text-slate-500 dark:text-zinc-400 truncate">
                                    <span class="font-mono truncate">{{ $user->nidn ?: '—' }}</span>
                                    <span class="shrink-0">·</span>
                                    <span class="truncate">{{ $user->email }}</span>
                                </div>
                            </div>

                            {{-- Arrow indicator --}}
                            <flux:icon.arrow-right
                                class="size-3 text-slate-300 dark:text-zinc-600
                           group-hover:text-amber-500 dark:group-hover:text-amber-400
                           group-hover:translate-x-0.5
                           transition-all shrink-0 me-1" />
                        </button>
                    @empty
                        <div class="animate-list-item flex flex-col items-center gap-1.5 py-8 text-center">
                            <flux:icon.user-group class="size-7 text-slate-300 dark:text-zinc-600" />
                            <p class="text-[11px] text-slate-500 dark:text-zinc-400">
                                {{ $search ? __('No users match your search.') : __('No other users found.') }}
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- FOOTER --}}
        <div
            class="shrink-0 flex justify-end px-4 py-2.5
                    border-t border-slate-200 dark:border-zinc-700
                    bg-slate-50/50 dark:bg-zinc-900/50">
            <x-button type="button" wire:click="close">
                {{ __('Close') }}
            </x-button>
        </div>
    </div>
</div>
