<?php

use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public bool $open = false;
    public bool $selectMode = false;
    public array $selected = [];

    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    #[Computed]
    public function notifications()
    {
        return auth()->user()->notifications()->latest()->limit(15)->get();
    }

    #[Computed]
    public function totalCount(): int
    {
        return auth()->user()->notifications()->count();
    }

    public function toggle(): void
    {
        $this->open = ! $this->open;
        if (! $this->open) {
            $this->resetSelection();
        }
    }

    public function toggleSelectMode(): void
    {
        $this->selectMode = ! $this->selectMode;
        $this->selected = [];
    }

    public function toggleSelect(string $id): void
    {
        if (in_array($id, $this->selected, true)) {
            $this->selected = array_values(array_diff($this->selected, [$id]));
        } else {
            $this->selected[] = $id;
        }
    }

    public function selectAll(): void
    {
        $this->selected = $this->notifications->pluck('id')->all();
    }

    public function deselectAll(): void
    {
        $this->selected = [];
    }

    public function markAsRead(string $id): void
    {
        if ($this->selectMode) {
            $this->toggleSelect($id);
            return;
        }

        $n = auth()->user()->notifications()->find($id);
        $n?->markAsRead();

        unset($this->unreadCount, $this->notifications, $this->totalCount);

        if ($n && ! empty($n->data['url'])) {
            $this->redirect($n->data['url'], navigate: true);
        }
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
        unset($this->unreadCount, $this->notifications, $this->totalCount);
    }

    public function deleteSelected(): void
    {
        if (empty($this->selected)) {
            return;
        }

        auth()->user()->notifications()->whereIn('id', $this->selected)->delete();

        $deleted = count($this->selected);
        $this->resetSelection();

        unset($this->unreadCount, $this->notifications, $this->totalCount);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "{$deleted} notification(s) deleted.",
        ]);
    }

    public function deleteAll(): void
    {
        auth()->user()->notifications()->delete();

        $this->resetSelection();
        $this->open = false;

        unset($this->unreadCount, $this->notifications, $this->totalCount);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'All notifications deleted.',
        ]);
    }

    public function deleteOne(string $id): void
    {
        auth()->user()->notifications()->where('id', $id)->delete();

        $this->selected = array_values(array_diff($this->selected, [$id]));

        unset($this->unreadCount, $this->notifications, $this->totalCount);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Notification deleted.',
        ]);
    }

    protected function resetSelection(): void
    {
        $this->selectMode = false;
        $this->selected = [];
    }

    public function titleLimit(?string $title): string
    {
        return Str::limit((string) ($title ?? '—'), 20, '…');
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            'pending'      => 'Pending',
            'submitted'    => 'Submitted',
            'under_review' => 'Under Review',
            'revised'      => 'Revised',
            'accepted'     => 'Accepted',
            'rejected'     => 'Rejected',
            default        => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    public function statusColor(string $status): string
    {
        return match ($status) {
            'pending'      => 'bg-slate-100 text-slate-600 dark:bg-zinc-800 dark:text-zinc-300',
            'submitted'    => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
            'under_review' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
            'revised'      => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
            'accepted'     => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
            'rejected'     => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
            default        => 'bg-slate-100 text-slate-600 dark:bg-zinc-800 dark:text-zinc-300',
        };
    }
};
?>

<div x-data="{ open: @entangle('open') }" class="relative">

    {{-- ══════════ BELL BUTTON ══════════ --}}
    <button @click="open = !open" type="button" aria-label="Notifications"
        class="cursor-pointer relative rounded-full p-1.5 sm:p-2 shadow-sm shadow-emerald-500/20 bg-transparent
               hover:bg-green-100 dark:hover:bg-stone-900
               transition-colors duration-150
               text-stone-500 hover:text-emerald-600
               dark:text-zinc-400 dark:hover:text-emerald-400">
        <flux:icon.bell class="size-4" />

        @if ($this->unreadCount > 0)
            <span class="absolute top-0.5 right-0.5 flex h-3.5 w-3.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full
                             bg-rose-400 opacity-75"></span>
                <span class="relative inline-flex items-center justify-center
                             rounded-full h-3.5 w-3.5
                             bg-rose-500 text-white text-[8px] font-bold">
                    {{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}
                </span>
            </span>
        @endif
    </button>

    {{-- ══════════ MOBILE BACKDROP ══════════ --}}
    <div x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        x-on:click="open = false"
        class="fixed inset-0 z-[60] bg-slate-900/40 backdrop-blur-sm sm:hidden">
    </div>

    {{-- ══════════ DROPDOWN / BOTTOM SHEET ══════════ --}}
    <div x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-cloak
        x-on:click.outside="open = false"
        x-on:keydown.escape.window="open = false"
        class="fixed inset-x-0 bottom-0 z-[70]
               sm:absolute sm:inset-auto sm:right-0 sm:top-full sm:mt-2
               sm:w-[380px] md:w-96 sm:max-w-[calc(100vw-1rem)]

               max-h-[60vh] sm:max-h-[32rem]
               h-auto

               flex flex-col
               rounded-t-2xl sm:rounded-xl
               border-t sm:border border-slate-200 dark:border-zinc-800
               bg-white dark:bg-zinc-900
               shadow-2xl sm:shadow-lg
               overflow-hidden
               pb-[env(safe-area-inset-bottom)]">

        {{-- Mobile drag handle --}}
        <div class="sm:hidden pt-2 pb-1 flex justify-center">
            <div class="w-10 h-1 rounded-full bg-slate-300 dark:bg-zinc-700"></div>
        </div>

        {{-- ══════════ HEADER ══════════ --}}
        <div class="shrink-0 flex items-center justify-between gap-2
                    px-3 py-2
                    border-b border-slate-100 dark:border-zinc-800">
            <div class="flex items-center gap-1.5 min-w-0">
                <flux:icon.bell class="size-3.5 text-slate-500 dark:text-zinc-400 shrink-0" />
                <span class="text-[12px] sm:text-xs font-semibold
                             text-slate-700 dark:text-zinc-200 truncate">
                    Notifications
                </span>
                @if ($this->totalCount > 0)
                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded
                                 bg-slate-100 text-slate-600
                                 dark:bg-zinc-800 dark:text-zinc-400">
                        {{ $this->totalCount }}
                    </span>
                @endif
            </div>

            <div class="flex items-center gap-1.5 shrink-0">
                @if ($this->unreadCount > 0 && ! $this->selectMode)
                    <button wire:click="markAllAsRead"
                        class="text-[10px] font-medium text-emerald-600 dark:text-emerald-400 hover:underline">
                        Mark read
                    </button>
                @endif

                @if ($this->totalCount > 0 && ! $this->selectMode)
                    <button wire:click="toggleSelectMode"
                        class="text-[10px] font-medium text-slate-500 dark:text-zinc-400 hover:underline">
                        Select
                    </button>
                @endif

                @if ($this->selectMode)
                    <button wire:click="toggleSelectMode"
                        class="text-[10px] font-medium text-slate-500 dark:text-zinc-400 hover:underline">
                        Cancel
                    </button>
                @endif

                <button x-on:click="open = false" aria-label="Close"
                    class="sm:hidden p-1 rounded-md
                           text-slate-400 hover:bg-slate-100
                           dark:text-zinc-500 dark:hover:bg-zinc-800 transition-colors">
                    <flux:icon.x-mark class="size-3.5" />
                </button>
            </div>
        </div>

        {{-- ══════════ BULK ACTION BAR ══════════ --}}
        @if ($this->selectMode)
            <div class="shrink-0 flex items-center justify-between gap-2 px-3 py-2
                        bg-slate-50 dark:bg-zinc-800/50
                        border-b border-slate-100 dark:border-zinc-800">
                <div class="flex items-center gap-2 min-w-0">
                    <button wire:click="{{ $this->selected ? 'deselectAll' : 'selectAll' }}"
                        class="text-[10px] font-medium text-emerald-600 dark:text-emerald-400
                               hover:underline whitespace-nowrap">
                        {{ $this->selected ? 'Deselect all' : 'Select all' }}
                    </button>
                    @if (count($this->selected) > 0)
                        <span class="text-[10px] text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                            {{ count($this->selected) }} selected
                        </span>
                    @endif
                </div>

                <button wire:click="deleteSelected"
                    wire:confirm="Delete {{ count($this->selected) }} selected notification(s)?"
                    @disabled(count($this->selected) === 0)
                    class="text-[10px] font-medium text-rose-600 dark:text-rose-400 hover:underline
                           disabled:opacity-40 disabled:cursor-not-allowed disabled:no-underline
                           whitespace-nowrap">
                    Delete
                </button>
            </div>
        @endif

        {{-- ══════════ LIST ══════════ --}}
        <div class="flex-1 min-h-0 overflow-y-auto overscroll-contain
                    [scrollbar-width:thin]
                    [&::-webkit-scrollbar]:w-1.5
                    [&::-webkit-scrollbar-thumb]:bg-slate-300
                    [&::-webkit-scrollbar-thumb]:rounded-full
                    dark:[&::-webkit-scrollbar-thumb]:bg-zinc-700">

            @forelse ($this->notifications as $n)
                @php
                    $isSelected = in_array($n->id, $this->selected, true);
                    $data = $n->data;
                    $isStatus = ($data['type'] ?? '') === 'status_changed';
                    $authorName = $data['author_name'] ?? null;
                    $oldStatus = $data['old_status'] ?? null;
                    $newStatus = $data['new_status'] ?? null;
                @endphp

                <div wire:key="n-{{ $n->id }}"
                    class="group relative flex items-start gap-2
                           px-3 py-2.5 sm:py-2
                           border-b border-slate-100 dark:border-zinc-800
                           hover:bg-slate-50 dark:hover:bg-zinc-800/50
                           active:bg-slate-100 dark:active:bg-zinc-800
                           transition-colors
                           {{ $n->read_at ? 'opacity-60' : '' }}
                           {{ $isSelected ? 'bg-emerald-50 dark:bg-emerald-900/10' : '' }}">

                    @if ($this->selectMode)
                        <button wire:click="toggleSelect('{{ $n->id }}')"
                            aria-label="Select notification"
                            class="mt-0.5 shrink-0 w-5 h-5 sm:w-4 sm:h-4
                                   rounded border flex items-center justify-center transition-colors
                                   {{ $isSelected
                                        ? 'bg-emerald-600 border-emerald-600 text-white'
                                        : 'border-slate-300 dark:border-zinc-600 hover:border-emerald-400' }}">
                            @if ($isSelected)
                                <svg class="w-3 h-3 sm:w-2.5 sm:h-2.5" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            @endif
                        </button>
                    @else
                        <span class="mt-1.5 w-1.5 h-1.5 rounded-full shrink-0
                                     {{ $n->read_at ? 'bg-slate-300 dark:bg-zinc-600' : 'bg-emerald-500' }}">
                        </span>
                    @endif

                    <button wire:click="markAsRead('{{ $n->id }}')" class="flex-1 min-w-0 text-left">
                        <p class="text-[12px] sm:text-[11px] font-semibold leading-tight
                                  text-slate-800 dark:text-zinc-100">
                            {{ $data['title'] ?? 'Notification' }}
                        </p>

                        @if (! empty($data['proposal_title']) || $authorName)
                            <div class="flex items-center justify-between gap-2 mt-0.5">
                                @if (! empty($data['proposal_title']))
                                    <p class="text-[10.5px] sm:text-[10px] truncate min-w-0
                                              text-slate-600 dark:text-zinc-300">
                                        "{{ $this->titleLimit($data['proposal_title']) }}"
                                    </p>
                                @endif

                                @if ($authorName)
                                    <span class="text-[10px] text-slate-500 dark:text-zinc-400
                                                 flex items-center gap-0.5 shrink-0 max-w-[140px]">
                                        <flux:icon.user class="size-2.5 shrink-0" />
                                        <span class="truncate">{{ $authorName }}</span>
                                    </span>
                                @endif
                            </div>
                        @endif

                        @if ($isStatus && $oldStatus && $newStatus)
                            <div class="flex items-center gap-1 mt-1 flex-wrap">
                                <span class="text-[8px] font-bold px-1 py-0.5 rounded-sm
                                             uppercase tracking-wide {{ $this->statusColor($oldStatus) }}">
                                    {{ $this->statusLabel($oldStatus) }}
                                </span>
                                <flux:icon.chevron-right class="size-2 text-slate-400 shrink-0" />
                                <span class="text-[8px] font-bold px-1 py-0.5 rounded-sm
                                             uppercase tracking-wide {{ $this->statusColor($newStatus) }}">
                                    {{ $this->statusLabel($newStatus) }}
                                </span>
                            </div>
                        @endif

                        <span class="block text-[9.5px] sm:text-[9px]
                                     text-slate-400 dark:text-zinc-500 mt-0.5">
                            {{ $n->created_at->diffForHumans() }}
                        </span>
                    </button>

                    @if (! $this->selectMode)
                        <button wire:click="deleteOne('{{ $n->id }}')"
                            wire:confirm="Delete this notification?"
                            aria-label="Delete"
                            class="shrink-0 w-7 h-7 sm:w-5 sm:h-5 rounded-md
                                   flex items-center justify-center
                                   text-slate-400 hover:text-rose-600
                                   hover:bg-rose-50 dark:hover:bg-rose-900/20
                                   sm:opacity-0 sm:group-hover:opacity-100 transition-all">
                            <flux:icon.x-mark class="size-3.5 sm:size-3" />
                        </flux:icon>
                    @endif
                </div>
            @empty
                <div class="flex flex-col items-center gap-2 px-3 py-10 text-center">
                    <div class="w-11 h-11 rounded-lg bg-slate-100 dark:bg-zinc-800
                                flex items-center justify-center">
                        <flux:icon.bell-slash class="size-5 text-slate-400 dark:text-zinc-600" />
                    </div>
                    <div>
                        <p class="text-[11.5px] font-semibold text-slate-700 dark:text-zinc-300">
                            No notifications yet
                        </p>
                        <p class="text-[10px] text-slate-500 dark:text-zinc-400 mt-0.5">
                            You'll see updates here when something happens.
                        </p>
                    </div>
                </div>
            @endforelse
        </div>

        {{-- ══════════ FOOTER ══════════ --}}
        @if ($this->totalCount > 0 && ! $this->selectMode)
            <div class="shrink-0 flex items-center justify-between gap-2 px-3 py-2
                        border-t border-slate-100 dark:border-zinc-800
                        bg-slate-50/50 dark:bg-zinc-950/30">
                <span class="text-[10px] text-slate-400 dark:text-zinc-500">
                    Showing {{ $this->notifications->count() }} of {{ $this->totalCount }}
                </span>

                <button wire:click="deleteAll"
                    wire:confirm="Delete ALL notifications? This action cannot be undone."
                    class="text-[10px] font-medium text-rose-600 dark:text-rose-400 hover:underline">
                    Delete all
                </button>
            </div>
        @endif
    </div>
</div>
