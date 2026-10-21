<?php

use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;
use Flux\Flux;

new #[Title('Activity Log')] class extends Component {
    public string $search = '';
    public string $logName = '';
    public string $event = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    public function resetFilters(): void
    {
        $this->reset('search', 'logName', 'event', 'dateFrom', 'dateTo');
    }

    #[On('delete-confirmed')]
    public function handleDeleteConfirmed(string $action, array $payload = []): void
    {
        match ($action) {
            'deleteLog'     => $this->deleteLog($payload['id'] ?? null),
            'deleteAllLogs' => $this->deleteAllLogs(),
            default         => null,
        };
    }

    public function deleteLog(?int $id): void
    {
        if (!$id) return;

        $log = Activity::find($id);
        if (!$log) {
            Flux::toast('Log already deleted.', variant: 'danger');
            return;
        }

        $log->delete();
        Flux::toast('Log deleted.', variant: 'success');
    }

    public function deleteAllLogs(): void
    {
        $query = $this->filteredQuery();
        $count = $query->count();
        $query->delete();

        Flux::toast("{$count} log(s) deleted.", variant: 'success');
    }

    protected function filteredQuery()
    {
        return Activity::query()
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('description', 'like', '%' . $this->search . '%')
                        ->orWhereHas('causer', fn($c) => $c->where('full_name', 'like', '%' . $this->search . '%'))
                        ->orWhereHas('subject', fn($s) => $s->where('title', 'like', '%' . $this->search . '%'));
                });
            })
            ->when($this->logName, fn($q) => $q->where('log_name', $this->logName))
            ->when($this->event, fn($q) => $q->where('event', $this->event))
            ->when($this->dateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('created_at', '<=', $this->dateTo));
    }

    public function render()
    {
        $logs = $this->filteredQuery()
            ->with(['causer', 'subject'])
            ->latest()
            ->limit(500)
            ->get();

        $logNames = Activity::distinct()->pluck('log_name')->filter()->values();
        $events   = Activity::distinct()->pluck('event')->filter()->values();

        return $this->view(['logs' => $logs, 'logNames' => $logNames, 'events' => $events]);
    }
};
?>

<div
    class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-50
            dark:from-zinc-950 dark:via-zinc-900 dark:to-zinc-950
            p-2.5 sm:p-3 lg:p-4">
    <div class="max-w-7xl mx-auto space-y-3">

        {{-- HEADER --}}
        <x-dashboard-header icon="command-line" title="Activity Logs"
            leading="Audit trail of every action performed by users across the system." />

        {{-- MAIN CARD --}}
        <div
            class="bg-white dark:bg-zinc-900 rounded-2xl
            shadow-sm shadow-slate-200/50 dark:shadow-zinc-950/50
            border border-slate-200 dark:border-zinc-800 overflow-hidden">

            {{-- ─────── TOOLBAR (RESPONSIVE) ─────── --}}
            <div
                class="px-2 sm:px-3 py-2
                border-b border-slate-200 dark:border-zinc-800
                bg-slate-50/50 dark:bg-zinc-900/50">

                <div class="flex flex-wrap items-center gap-1.5 min-w-0">

                    {{-- ─────── STATS BADGE ─────── --}}
                    @if ($logs->count() > 0)
                        <div
                            class="shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                            bg-violet-50 dark:bg-violet-900/20
                            border border-violet-100 dark:border-violet-900/40">
                            <flux:icon.clock class="size-3 text-violet-600 dark:text-violet-400" />
                            <span class="text-[10.5px] font-semibold text-violet-700 dark:text-violet-300">
                                {{ $logs->count() }}
                            </span>
                            <span class="text-[10px] text-violet-600/70 dark:text-violet-400/70 hidden sm:inline">
                                logs
                            </span>
                        </div>
                    @else
                        <div
                            class="shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                            bg-slate-50 dark:bg-zinc-800/40
                            border border-slate-100 dark:border-zinc-800">
                            <flux:icon.clock class="size-3 text-slate-500 dark:text-zinc-400" />
                            <span class="text-[10.5px] font-medium text-slate-500 dark:text-zinc-400">
                                No logs
                            </span>
                        </div>
                    @endif

                    {{-- ─────── FILTER: LOG NAME ─────── --}}
                    <div class="shrink-0">
                        <flux:select wire:model.live="logName" color="violet">
                            <flux:select.option value="">All Logs</flux:select.option>
                            @foreach ($logNames as $name)
                                <flux:select.option value="{{ $name }}">{{ $name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    {{-- ─────── FILTER: EVENT ─────── --}}
                    <div class="shrink-0">
                        <flux:select wire:model.live="event" color="violet" maxWidth="w-auto">
                            <flux:select.option value="">All Events</flux:select.option>
                            @foreach ($events as $ev)
                                <flux:select.option value="{{ $ev }}">{{ $ev }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    {{-- ─────── DATE RANGE ─────── --}}
                    <div
                        class="shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-full
                            bg-white dark:bg-zinc-800
                            text-slate-700 dark:text-zinc-200
                            text-[11.5px] font-body
                            border border-slate-200 dark:border-zinc-700
                            shadow-sm shadow-slate-200/60 dark:shadow-zinc-950/40
                            hover:shadow-sm hover:shadow-violet-500/20
                            hover:border-slate-300 dark:hover:border-zinc-600
                            focus-within:outline-none focus-within:ring-2
                            focus-within:ring-violet-500/25 focus-within:border-violet-500
                            focus-within:shadow-md
                            transition-all duration-200 ease-out">

                        {{-- Icon kiri --}}
                        <flux:icon.calendar-days
                            class="size-3.5 text-slate-400 dark:text-zinc-500 shrink-0 pointer-events-none" />

                        {{-- FROM --}}
                        <input type="date" wire:model.live="dateFrom" aria-label="From date"
                            class="bg-transparent border-0 p-0
                                text-[11px] font-body leading-none
                                text-slate-700 dark:text-zinc-200
                                placeholder:text-slate-400 dark:placeholder:text-zinc-500
                                focus:outline-none focus:ring-0 cursor-pointer
                                w-[92px] max-w-[92px]
                                [color-scheme:light] dark:[color-scheme:dark]
                                [&::-webkit-calendar-picker-indicator]:cursor-pointer
                                [&::-webkit-calendar-picker-indicator]:opacity-60
                                [&::-webkit-calendar-picker-indicator]:hover:opacity-100" />

                        {{-- Separator --}}
                        <span class="text-slate-300 dark:text-zinc-600 text-[10px] leading-none select-none shrink-0">
                            —
                        </span>

                        {{-- TO --}}
                        <input type="date" wire:model.live="dateTo" aria-label="To date"
                            class="bg-transparent border-0 p-0
                        text-[11px] font-body leading-none
                        text-slate-700 dark:text-zinc-200
                        placeholder:text-slate-400 dark:placeholder:text-zinc-500
                        focus:outline-none focus:ring-0 cursor-pointer
                        w-[92px] max-w-[92px]
                        [color-scheme:light] dark:[color-scheme:dark]
                        [&::-webkit-calendar-picker-indicator]:cursor-pointer
                        [&::-webkit-calendar-picker-indicator]:opacity-60
                        [&::-webkit-calendar-picker-indicator]:hover:opacity-100" />
                    </div>
                    {{-- ─────── DELETE ALL ─────── --}}
                    @if ($logs->count() > 0)
                        <div class="shrink-0 order-4 sm:order-5">
                            <flux:button variant="danger" size="sm"
                                x-data
                                x-on:click="$dispatch('confirm-delete', {
                                    title: 'Delete all logs?',
                                    message: 'This will permanently delete {{ $logs->count() }} log entries matching the current filter.',
                                    confirmLabel: 'Delete All',
                                    action: 'deleteAllLogs',
                                    payload: {}
                                })"
                                icon="trash"
                                class="!text-[10.5px] !rounded-full">
                                Delete All
                            </flux:button>
                        </div>
                    @endif
                    {{-- ─────── RESET BUTTON ─────── --}}
                    @if ($search || $logName || $event || $dateFrom || $dateTo)
                        <div class="shrink-0 order-4 sm:order-5 ml-auto sm:ml-0">
                            <flux:button variant="ghost" size="sm" wire:click="resetFilters" icon="x-mark"
                                class="!text-[10.5px] !rounded-full">
                                Reset
                            </flux:button>
                        </div>
                    @endif

                    {{-- ─────── SEARCH ─────── --}}
                    <div
                        class="order-5 sm:order-4
                        w-full sm:w-auto
                        sm:ml-auto
                        md:w-44 lg:w-56
                        min-w-0">
                        <x-input-search name="q" wire:model.live.debounce.300ms="search" id="search-activity-log"
                            placeholder="Search user, action, model..." class="w-full !text-[10.5px]" />
                    </div>
                </div>
            </div>

            {{-- ─────── TABLE (RESPONSIVE) ─────── --}}
            <div class="relative">
                {{-- Swipe hint --}}
                <div x-data="{ showHint: true }" x-init="setTimeout(() => showHint = false, 3500)" x-show="showHint"
                    x-transition:leave="transition ease-in duration-500" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="pointer-events-none absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2
                   z-20 md:hidden inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full
                   bg-slate-900/85 dark:bg-zinc-700/90 backdrop-blur-sm
                   text-white text-[10.5px] font-medium shadow-lg">
                    <flux:icon.arrows-right-left class="size-3" />
                    Swipe to see more
                </div>

                <div
                    class="overflow-x-auto overscroll-x-contain scroll-smooth
                    [scrollbar-width:thin]
                    [&::-webkit-scrollbar]:h-1.5
                    [&::-webkit-scrollbar-thumb]:bg-slate-300
                    [&::-webkit-scrollbar-thumb]:rounded-full
                    dark:[&::-webkit-scrollbar-thumb]:bg-zinc-700">
                    <table class="w-full min-w-[560px] sm:min-w-[640px] lg:min-w-[820px]">
                        <thead>
                            <tr
                                class="bg-slate-50/80 dark:bg-zinc-900/50
                               border-b border-slate-200 dark:border-zinc-800">
                                <th
                                    class="px-2 sm:px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    Activity
                                </th>
                                <th
                                    class="px-2 sm:px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    User
                                </th>
                                <th
                                    class="hidden md:table-cell px-2 sm:px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    Subject
                                </th>
                                <th
                                    class="px-2 sm:px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    Event
                                </th>
                                <th
                                    class="px-2 sm:px-3 py-1.5 text-right text-[9.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    When
                                </th>
                                <th
                                    class="px-2 sm:px-3 py-1.5 text-right text-[9.5px] font-semibold uppercase tracking-wider
                                    text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/70">
                            @forelse ($logs as $log)
                                @php
                                    $causer = $log->causer;
                                    $subject = $log->subject;
                                    $subjectType = $log->subject_type ? class_basename($log->subject_type) : null;
                                    $subjectTitle = null;
                                    if ($subject) {
                                        $subjectTitle = method_exists($subject, 'getActivitylogSubjectTitle')
                                            ? $subject->getActivitylogSubjectTitle()
                                            : $subject->title ?? ($subject->name ?? null);
                                    }

                                    $eventMeta = match ($log->event) {
                                        'created' => [
                                            'icon' => 'plus',
                                            'label' => 'Created',
                                            'class' =>
                                                'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                                        ],
                                        'updated' => [
                                            'icon' => 'pencil',
                                            'label' => 'Updated',
                                            'class' =>
                                                'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                                        ],
                                        'deleted' => [
                                            'icon' => 'trash',
                                            'label' => 'Deleted',
                                            'class' =>
                                                'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
                                        ],
                                        'login' => [
                                            'icon' => 'arrow-right-on-rectangle',
                                            'label' => 'Login',
                                            'class' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300',
                                        ],
                                        'logout' => [
                                            'icon' => 'arrow-left-on-rectangle',
                                            'label' => 'Logout',
                                            'class' =>
                                                'bg-slate-100 text-slate-700 dark:bg-zinc-800 dark:text-zinc-300',
                                        ],
                                        default => [
                                            'icon' => 'clock',
                                            'label' => $log->event ? ucfirst($log->event) : '—',
                                            'class' =>
                                                'bg-slate-100 text-slate-700 dark:bg-zinc-800 dark:text-zinc-300',
                                        ],
                                    };
                                @endphp
                                <tr wire:key="activity-log-{{ $log->id }}" x-data
                                    x-on:click="$dispatch('open-log-detail', { id: {{ $log->id }} })"
                                    class="group cursor-pointer transition-colors duration-150
                                        hover:bg-slate-50/70 dark:hover:bg-zinc-800/40">

                                    {{-- ACTIVITY / DESCRIPTION --}}
                                    <td class="px-2 sm:px-3 py-2">
                                        <div class="flex items-start gap-2">
                                            <div
                                                class="w-6 h-6 sm:w-7 sm:h-7 rounded-full shrink-0
                                                bg-gradient-to-br from-violet-100 to-indigo-50
                                                dark:from-violet-900/30 dark:to-indigo-900/20
                                                flex items-center justify-center
                                                ring-1 ring-white/40 dark:ring-zinc-800/40
                                                group-hover:scale-105 transition-transform">
                                                <flux:icon :name="$eventMeta['icon']"
                                                    class="size-3 sm:size-3.5 text-violet-600 dark:text-violet-400" />
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-[11px] sm:text-[11.5px] font-semibold leading-tight truncate
                                                          text-slate-900 dark:text-white max-w-[200px] sm:max-w-[260px]"
                                                    title="{{ $log->description }}">
                                                    {{ $log->description }}
                                                </p>
                                                <div class="mt-0.5 flex items-center gap-1.5 flex-wrap text-[9.5px]">
                                                    @if ($log->log_name)
                                                        <span
                                                            class="px-1.5 py-0.5 rounded-full font-semibold whitespace-nowrap
                                                                 bg-indigo-100 text-indigo-700
                                                                 dark:bg-indigo-900/30 dark:text-indigo-300">
                                                            {{ $log->log_name }}
                                                        </span>
                                                    @endif
                                                    @if ($log->getProperty('ip'))
                                                        <span class="text-slate-500 dark:text-zinc-500">
                                                            IP: {{ $log->getProperty('ip') }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- USER (CAUSER) --}}
                                    <td class="px-2 sm:px-3 py-2">
                                        @if ($causer)
                                            <div class="flex items-center gap-2 min-w-0">
                                                <div
                                                    class="w-6 h-6 rounded-full shrink-0
                                                    bg-violet-100 text-violet-700
                                                    dark:bg-violet-900/40 dark:text-violet-300
                                                    flex items-center justify-center
                                                    text-[9px] font-bold">
                                                    {{ strtoupper(substr($causer->full_name ?? ($causer->name ?? 'U'), 0, 1)) }}
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-[10.5px] font-medium truncate
                                                              text-slate-700 dark:text-zinc-300 max-w-[150px]"
                                                        title="{{ $causer->full_name ?? $causer->name }}">
                                                        {{ $causer->full_name ?? $causer->name }}
                                                    </p>
                                                    <p class="text-[9.5px] text-slate-500 dark:text-zinc-500 truncate max-w-[150px]"
                                                        title="{{ $causer->nidn ?? $causer->email }}">
                                                        {{ $causer->nidn ?? ($causer->email ?? '') }}
                                                    </p>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-[10.5px] text-slate-400 dark:text-zinc-500 italic">
                                                System
                                            </span>
                                        @endif
                                    </td>

                                    {{-- SUBJECT --}}
                                    <td class="hidden md:table-cell px-2 sm:px-3 py-2">
                                        @if ($subjectType)
                                            <p
                                                class="text-[10.5px] font-medium text-slate-700 dark:text-zinc-300 truncate max-w-[160px]">
                                                {{ $subjectType }}
                                            </p>
                                            @if ($subjectTitle)
                                                <p class="text-[9.5px] text-slate-500 dark:text-zinc-500 truncate max-w-[160px]"
                                                    title="{{ $subjectTitle }}">
                                                    {{ Str::limit($subjectTitle, 32) }}
                                                </p>
                                            @endif
                                        @else
                                            <span
                                                class="text-[10.5px] text-slate-400 dark:text-zinc-500 italic">—</span>
                                        @endif
                                    </td>

                                    {{-- EVENT BADGE --}}
                                    <td class="px-2 sm:px-3 py-2">
                                        <span
                                            class="inline-flex items-center gap-1 px-1.5 sm:px-2 py-0.5 rounded-full
                                                   text-[9.5px] font-semibold whitespace-nowrap
                                                   {{ $eventMeta['class'] }}">
                                            <flux:icon :name="$eventMeta['icon']" class="size-2.5" />
                                            {{ $eventMeta['label'] }}
                                        </span>
                                    </td>

                                    {{-- WHEN --}}
                                    <td class="px-2 sm:px-3 py-2 text-right">
                                        <p class="text-[10.5px] font-medium text-slate-700 dark:text-zinc-300 whitespace-nowrap"
                                            title="{{ $log->created_at->format('d M Y, H:i:s') }}">
                                            {{ $log->created_at->diffForHumans() }}
                                        </p>
                                        <p class="text-[9.5px] text-slate-500 dark:text-zinc-500 whitespace-nowrap">
                                            {{ $log->created_at->format('d M Y') }}
                                        </p>
                                    </td>

                                    {{-- Delete --}}
                                    <td class="px-2 sm:px-3 py-2 text-right" x-on:click.stop>
                                        <flux:button size="xs" variant="ghost" icon="trash"
                                            x-on:click="$dispatch('confirm-delete', {
                                                title: 'Delete this log?',
                                                message: 'The following log entry will be permanently removed:',
                                                subject: @js($log->description),
                                                note: @js($log->created_at->format('d M Y, H:i:s') . ' · ' . ($log->log_name ?? '—')),
                                                confirmLabel: 'Delete',
                                                action: 'deleteLog',
                                                payload: { id: {{ $log->id }} }
                                            })"
                                            class="!p-1 rounded-full !text-rose-500
                                                hover:!bg-rose-50 dark:hover:!bg-rose-900/20
                                                hover:scale-110 active:scale-95
                                                opacity-60 group-hover:opacity-100 transition-all duration-150"
                                            aria-label="Delete log" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-10">
                                        <div class="flex flex-col items-center gap-2 text-center">
                                            <div
                                                class="w-11 h-11 rounded-full bg-slate-100 dark:bg-zinc-800
                                                flex items-center justify-center">
                                                <flux:icon.clock class="size-5 text-slate-400 dark:text-zinc-600" />
                                            </div>
                                            <div>
                                                <p class="text-[12px] font-semibold text-slate-900 dark:text-white">
                                                    No activity logs
                                                </p>
                                                <p class="text-[10.5px] text-slate-500 dark:text-zinc-400 mt-0.5">
                                                    @if ($search || $logName || $event || $dateFrom || $dateTo)
                                                        No results match your filters.
                                                    @else
                                                        Activity logs will appear here once users start interacting.
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <livewire:admin.spatie.show-detail />
    <x-confirm-delete />
</div>
