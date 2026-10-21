<?php

use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

new class extends Component {
    public ?int $activityId = null;

    public function load(int $id): void
    {
        $this->activityId = $id;
    }

    public function close(): void
    {
        $this->activityId = null;
    }

    public function render()
    {
        $activity = $this->activityId
            ? Activity::with(['causer', 'subject'])->find($this->activityId)
            : null;

        return $this->view([
            'activity' => $activity,
        ]);
    }
};
?>

<div
    x-data="{
        show: false,
        showRaw: false,
        init() {
            window.addEventListener('open-log-detail', (e) => {
                this.showRaw = false;
                $wire.load(e.detail.id);
                this.show = true;
            });
            window.addEventListener('close-log-detail', () => {
                $wire.close();
                this.show = false;
            });
        }
    }"
    x-show="show"
    x-transition.opacity
    x-cloak
    x-on:keydown.escape.window="show = false; $wire.close()"
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4"
    @click.self="show = false; $wire.close()">

    <div
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="flex max-h-[92vh] w-full sm:max-w-2xl flex-col overflow-hidden
               bg-white dark:bg-zinc-900 rounded-t-3xl sm:rounded-3xl shadow-2xl
               border border-slate-200 dark:border-zinc-700"
        @click.stop>

        @if ($activity)
            @php
                $causer = $activity->causer;
                $subject = $activity->subject;
                $subjectType = $activity->subject_type ? class_basename($activity->subject_type) : null;
                $changes = $activity->attribute_changes;
                $old = $changes['old'] ?? [];
                $new = $changes['attributes'] ?? [];
                $properties = $activity->properties?->toArray() ?? [];

                $changedKeys = collect(array_keys($old))
                    ->merge(array_keys($new))
                    ->unique()
                    ->filter(fn ($k) => ($old[$k] ?? null) !== ($new[$k] ?? null))
                    ->values();
                $changedCount = $changedKeys->count();

                $eventMeta = match ($activity->event) {
                    'created' => ['icon' => 'plus', 'label' => 'Created',
                                  'grad' => 'from-emerald-600 to-emerald-500'],
                    'updated' => ['icon' => 'pencil', 'label' => 'Updated',
                                  'grad' => 'from-amber-600 to-amber-500'],
                    'deleted' => ['icon' => 'trash', 'label' => 'Deleted',
                                  'grad' => 'from-rose-600 to-rose-500'],
                    'login'   => ['icon' => 'arrow-right-on-rectangle', 'label' => 'Login',
                                  'grad' => 'from-sky-600 to-sky-500'],
                    'logout'  => ['icon' => 'arrow-left-on-rectangle', 'label' => 'Logout',
                                  'grad' => 'from-slate-700 to-slate-600'],
                    default   => ['icon' => 'clock', 'label' => $activity->event ? ucfirst($activity->event) : 'Activity',
                                  'grad' => 'from-violet-600 to-violet-500'],
                };

                $fmt = function ($v, int $limit = 120) {
                    if ($v === null) return '—';
                    if (is_bool($v)) return $v ? 'true' : 'false';
                    if (is_array($v)) return json_encode($v);
                    $s = (string) $v;
                    return \Illuminate\Support\Str::limit($s, $limit);
                };
            @endphp

            {{-- ══════════ HEADER ══════════ --}}
            <div class="shrink-0 bg-gradient-to-r {{ $eventMeta['grad'] }} px-5 py-3.5">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                            <flux:icon :name="$eventMeta['icon']" class="size-4 text-white" />
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-white leading-tight truncate">
                                {{ $activity->description }}
                            </h3>
                            <p class="text-[11px] text-white/75 mt-0.5 leading-tight truncate">
                                {{ $eventMeta['label'] }}
                                @if ($activity->log_name) · {{ $activity->log_name }} @endif
                                · {{ $activity->created_at->format('d M Y, H:i:s') }}
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="show = false; $wire.close()" aria-label="Close"
                        class="shrink-0 w-7 h-7 rounded-full flex items-center justify-center
                               text-white/80 hover:text-white hover:bg-white/10 transition-colors">
                        <flux:icon.x-mark class="size-3.5" />
                    </button>
                </div>
            </div>

            {{-- ══════════ BODY ══════════ --}}
            <div class="flex-1 overflow-y-auto px-5 py-4 space-y-5">

                {{-- ─── Section: Overview ─── --}}
                <section class="space-y-3">
                    <header class="flex items-center gap-2">
                        <flux:icon.document-magnifying-glass class="size-3.5 text-violet-600 dark:text-violet-400" />
                        <h4 class="text-[10.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400">
                            Overview
                        </h4>
                        <div class="flex-1 h-px bg-slate-200 dark:bg-zinc-700"></div>
                    </header>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        {{-- Event --}}
                        <div class="p-2.5 rounded-2xl bg-slate-50 dark:bg-zinc-800/40
                                    border border-slate-200 dark:border-zinc-800">
                            <p class="text-[9.5px] font-semibold uppercase tracking-wider
                                      text-slate-400 dark:text-zinc-500">
                                Event
                            </p>
                            <p class="mt-0.5 text-[11px] font-semibold
                                      text-slate-700 dark:text-zinc-200">
                                {{ $eventMeta['label'] }}
                            </p>
                        </div>

                        {{-- Log Name --}}
                        <div class="p-2.5 rounded-2xl bg-slate-50 dark:bg-zinc-800/40
                                    border border-slate-200 dark:border-zinc-800">
                            <p class="text-[9.5px] font-semibold uppercase tracking-wider
                                      text-slate-400 dark:text-zinc-500">
                                Log Name
                            </p>
                            <p class="mt-0.5 text-[11px] font-semibold truncate
                                      text-slate-700 dark:text-zinc-200"
                                title="{{ $activity->log_name ?? '—' }}">
                                {{ $activity->log_name ?? '—' }}
                            </p>
                        </div>

                        {{-- Timestamp --}}
                        <div class="p-2.5 rounded-2xl bg-slate-50 dark:bg-zinc-800/40
                                    border border-slate-200 dark:border-zinc-800">
                            <p class="text-[9.5px] font-semibold uppercase tracking-wider
                                      text-slate-400 dark:text-zinc-500">
                                Timestamp
                            </p>
                            <p class="mt-0.5 text-[11px] font-semibold
                                      text-slate-700 dark:text-zinc-200">
                                {{ $activity->created_at->format('d M Y, H:i:s') }}
                            </p>
                            <p class="text-[9.5px] text-slate-400 dark:text-zinc-500">
                                {{ $activity->created_at->diffForHumans() }} · {{ config('app.timezone') }}
                            </p>
                        </div>

                        {{-- Changes count --}}
                        <div class="p-2.5 rounded-2xl bg-slate-50 dark:bg-zinc-800/40
                                    border border-slate-200 dark:border-zinc-800">
                            <p class="text-[9.5px] font-semibold uppercase tracking-wider
                                      text-slate-400 dark:text-zinc-500">
                                Changes
                            </p>
                            <p class="mt-0.5 text-[11px] font-semibold
                                      text-slate-700 dark:text-zinc-200">
                                {{ $changedCount }} field{{ $changedCount !== 1 ? 's' : '' }}
                            </p>
                        </div>

                        {{-- Batch UUID (full width) --}}
                        @if ($activity->batch_uuid)
                            <div class="sm:col-span-2 p-2.5 rounded-2xl bg-slate-50 dark:bg-zinc-800/40
                                        border border-slate-200 dark:border-zinc-800">
                                <p class="text-[9.5px] font-semibold uppercase tracking-wider
                                          text-slate-400 dark:text-zinc-500">
                                    Batch UUID
                                </p>
                                <p class="mt-0.5 text-[10.5px] font-mono break-all
                                          text-slate-700 dark:text-zinc-200">
                                    {{ $activity->batch_uuid }}
                                </p>
                            </div>
                        @endif
                    </div>
                </section>

                {{-- ─── Section: Actor ─── --}}
                <section class="space-y-3">
                    <header class="flex items-center gap-2">
                        <flux:icon.user-circle class="size-3.5 text-violet-600 dark:text-violet-400" />
                        <h4 class="text-[10.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400">
                            Actor
                        </h4>
                        <div class="flex-1 h-px bg-slate-200 dark:bg-zinc-700"></div>
                    </header>

                    @if ($causer)
                        <div class="flex items-center gap-2.5 p-2.5 rounded-2xl
                                    bg-slate-50 dark:bg-zinc-800/40
                                    border border-slate-200 dark:border-zinc-800">
                            <div class="w-9 h-9 rounded-full shrink-0
                                        bg-violet-100 text-violet-700
                                        dark:bg-violet-900/40 dark:text-violet-300
                                        flex items-center justify-center text-xs font-bold">
                                {{ strtoupper(substr($causer->full_name ?? ($causer->name ?? 'U'), 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-[11.5px] font-semibold truncate
                                          text-slate-900 dark:text-zinc-100">
                                    {{ $causer->full_name ?? ($causer->name ?? '—') }}
                                </p>
                                <p class="text-[10px] text-slate-500 dark:text-zinc-500 truncate">
                                    {{ $causer->email ?? '—' }}
                                </p>
                                @if (!empty($causer->nidn))
                                    <p class="text-[10px] text-slate-500 dark:text-zinc-500 truncate">
                                        NIDN: {{ $causer->nidn }}
                                    </p>
                                @endif
                            </div>
                            <div class="shrink-0 text-[9.5px] text-slate-400 dark:text-zinc-500 text-right">
                                <p>ID: {{ $causer->id }}</p>
                                <p>{{ class_basename($causer) }}</p>
                                @if (!empty($causer->role?->name))
                                    <p class="mt-0.5 px-1.5 py-0.5 rounded-full inline-block
                                              bg-violet-100 text-violet-700
                                              dark:bg-violet-900/40 dark:text-violet-300">
                                        {{ $causer->role->name }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @else
                        <p class="text-[11px] text-slate-400 dark:text-zinc-500 italic">System</p>
                    @endif
                </section>

                {{-- ─── Section: Subject ─── --}}
                @if ($subjectType)
                    <section class="space-y-3">
                        <header class="flex items-center gap-2">
                            <flux:icon.cube class="size-3.5 text-violet-600 dark:text-violet-400" />
                            <h4 class="text-[10.5px] font-semibold uppercase tracking-wider
                                       text-slate-500 dark:text-zinc-400">
                                Subject
                            </h4>
                            <div class="flex-1 h-px bg-slate-200 dark:bg-zinc-700"></div>
                        </header>

                        <div class="p-2.5 rounded-2xl
                                    bg-slate-50 dark:bg-zinc-800/40
                                    border border-slate-200 dark:border-zinc-800
                                    space-y-1.5">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10px] font-semibold uppercase tracking-wider
                                             text-slate-400 dark:text-zinc-500">
                                    Model
                                </span>
                                <span class="text-[11px] font-medium
                                             text-slate-700 dark:text-zinc-200">
                                    {{ $subjectType }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10px] font-semibold uppercase tracking-wider
                                             text-slate-400 dark:text-zinc-500">
                                    ID
                                </span>
                                <span class="text-[11px] font-mono
                                             text-slate-700 dark:text-zinc-200">
                                    {{ $activity->subject_id ?? '—' }}
                                </span>
                            </div>
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-[10px] font-semibold uppercase tracking-wider shrink-0
                                             text-slate-400 dark:text-zinc-500">
                                    Class
                                </span>
                                <span class="text-[10px] font-mono text-right break-all
                                             text-slate-600 dark:text-zinc-300">
                                    {{ $activity->subject_type }}
                                </span>
                            </div>
                            @if ($subject)
                                @php
                                    $title = method_exists($subject, 'getActivitylogSubjectTitle')
                                        ? $subject->getActivitylogSubjectTitle()
                                        : ($subject->title ?? ($subject->name ?? null));
                                @endphp
                                @if ($title)
                                    <div class="flex items-start justify-between gap-2">
                                        <span class="text-[10px] font-semibold uppercase tracking-wider
                                                     text-slate-400 dark:text-zinc-500 shrink-0">
                                            Title
                                        </span>
                                        <span class="text-[11px] font-medium text-right break-words
                                                     text-slate-700 dark:text-zinc-200">
                                            {{ $title }}
                                        </span>
                                    </div>
                                @endif
                                @if (method_exists($subject, 'getKey'))
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-[10px] font-semibold uppercase tracking-wider
                                                     text-slate-400 dark:text-zinc-500">
                                            Primary Key
                                        </span>
                                        <span class="text-[11px] font-mono
                                                     text-slate-700 dark:text-zinc-200">
                                            {{ $subject->getKey() }}
                                        </span>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </section>
                @endif

                {{-- ─── Section: Changes (Old / New) ─── --}}
                @if ($changedCount > 0)
                    <section class="space-y-3">
                        <header class="flex items-center gap-2">
                            <flux:icon.arrow-path class="size-3.5 text-violet-600 dark:text-violet-400" />
                            <h4 class="text-[10.5px] font-semibold uppercase tracking-wider
                                       text-slate-500 dark:text-zinc-400">
                                Changes
                            </h4>
                            <span class="px-1.5 py-0.5 rounded-full text-[9px] font-bold
                                         bg-violet-100 text-violet-700
                                         dark:bg-violet-900/40 dark:text-violet-300">
                                {{ $changedCount }}
                            </span>
                            <div class="flex-1 h-px bg-slate-200 dark:bg-zinc-700"></div>
                        </header>

                        <div class="rounded-2xl overflow-hidden
                                    border border-slate-200 dark:border-zinc-800">
                            <table class="w-full text-[10.5px]">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-zinc-800/60">
                                        <th class="px-2.5 py-1.5 text-left font-semibold uppercase tracking-wider
                                                   text-[9.5px] text-slate-500 dark:text-zinc-400">
                                            Field
                                        </th>
                                        <th class="px-2.5 py-1.5 text-left font-semibold uppercase tracking-wider
                                                   text-[9.5px] text-rose-500 dark:text-rose-400">
                                            Old Value
                                        </th>
                                        <th class="px-2.5 py-1.5 text-left font-semibold uppercase tracking-wider
                                                   text-[9.5px] text-emerald-600 dark:text-emerald-400">
                                            New Value
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/70">
                                    @foreach ($changedKeys as $key)
                                        @php
                                            $oldVal = $old[$key] ?? null;
                                            $newVal = $new[$key] ?? null;
                                        @endphp
                                        <tr>
                                            <td class="px-2.5 py-1.5 align-top font-medium
                                                       text-slate-700 dark:text-zinc-200">
                                                {{ $key }}
                                            </td>
                                            <td class="px-2.5 py-1.5 align-top break-all
                                                       text-rose-600 dark:text-rose-400">
                                                @if ($oldVal === null)
                                                    <span class="italic text-slate-400 dark:text-zinc-500">null</span>
                                                @else
                                                    {{ $fmt($oldVal) }}
                                                @endif
                                            </td>
                                            <td class="px-2.5 py-1.5 align-top break-all
                                                       text-emerald-700 dark:text-emerald-400">
                                                @if ($newVal === null)
                                                    <span class="italic text-slate-400 dark:text-zinc-500">null</span>
                                                @else
                                                    {{ $fmt($newVal) }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif

                {{-- ─── Section: Properties (IP, User Agent, dll) ─── --}}
                @if (!empty($properties))
                    <section class="space-y-3">
                        <header class="flex items-center gap-2">
                            <flux:icon.information-circle class="size-3.5 text-violet-600 dark:text-violet-400" />
                            <h4 class="text-[10.5px] font-semibold uppercase tracking-wider
                                       text-slate-500 dark:text-zinc-400">
                                Properties
                            </h4>
                            <div class="flex-1 h-px bg-slate-200 dark:bg-zinc-700"></div>
                        </header>

                        <div class="rounded-2xl overflow-hidden
                                    border border-slate-200 dark:border-zinc-800
                                    divide-y divide-slate-100 dark:divide-zinc-800/70">
                            @foreach ($properties as $k => $v)
                                <div class="flex items-start justify-between gap-3 px-2.5 py-1.5">
                                    <span class="text-[10px] font-semibold uppercase tracking-wider shrink-0
                                                 text-slate-400 dark:text-zinc-500">
                                        {{ $k }}
                                    </span>
                                    <span class="text-[10.5px] text-right break-all
                                                 text-slate-700 dark:text-zinc-200">
                                        @if (is_array($v) || is_object($v))
                                            <pre class="text-left whitespace-pre-wrap text-[10px] font-mono
                                                        text-slate-600 dark:text-zinc-300">{{ json_encode($v, JSON_PRETTY_PRINT) }}</pre>
                                        @elseif (is_bool($v))
                                            <span class="font-mono">{{ $v ? 'true' : 'false' }}</span>
                                        @elseif ($v === null)
                                            <span class="italic text-slate-400 dark:text-zinc-500">null</span>
                                        @else
                                            {{ $v }}
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                {{-- ─── Section: Raw JSON (collapsible) ─── --}}
                <section class="space-y-3">
                    <header class="flex items-center gap-2">
                        <flux:icon.code-bracket class="size-3.5 text-violet-600 dark:text-violet-400" />
                        <h4 class="text-[10.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400">
                            Raw Payload
                        </h4>
                        <div class="flex-1 h-px bg-slate-200 dark:bg-zinc-700"></div>
                        <button type="button"
                            @click="showRaw = !showRaw"
                            class="text-[10px] font-medium text-violet-600 dark:text-violet-400
                                   hover:underline">
                            <span x-text="showRaw ? 'Hide' : 'Show'"></span>
                        </button>
                    </header>

                    <div x-show="showRaw" x-cloak x-transition
                        class="rounded-2xl overflow-hidden
                               bg-slate-900 dark:bg-black
                               border border-slate-700 dark:border-zinc-800">
                        <div class="flex items-center justify-between px-2.5 py-1.5
                                    border-b border-slate-700 dark:border-zinc-800">
                            <span class="text-[9.5px] font-mono text-slate-400">JSON</span>
                        </div>
                        <pre class="p-2.5 text-[10px] leading-relaxed font-mono
                                    text-emerald-300 overflow-x-auto max-h-64 overflow-y-auto">{{ json_encode([
                                        'id' => $activity->id,
                                        'log_name' => $activity->log_name,
                                        'description' => $activity->description,
                                        'event' => $activity->event,
                                        'subject_type' => $activity->subject_type,
                                        'subject_id' => $activity->subject_id,
                                        'causer_type' => $activity->causer_type,
                                        'causer_id' => $activity->causer_id,
                                        'attribute_changes' => $changes,
                                        'properties' => $properties,
                                        'batch_uuid' => $activity->batch_uuid,
                                        'created_at' => $activity->created_at?->toIso8601String(),
                                        'updated_at' => $activity->updated_at?->toIso8601String(),
                                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </section>
            </div>

            {{-- ══════════ FOOTER ══════════ --}}
            <div class="shrink-0 flex flex-col-reverse sm:flex-row sm:justify-between gap-2
                        px-5 py-3.5
                        border-t border-slate-200 dark:border-zinc-700
                        bg-slate-50/50 dark:bg-zinc-900/50">

                <div class="text-[10px] text-slate-400 dark:text-zinc-500 flex items-center gap-1.5">
                    <flux:icon.hashtag class="size-3" />
                    <span class="font-mono">#{{ $activity->id }}</span>
                </div>

                <button type="button" @click="show = false; $wire.close()"
                    class="w-full sm:w-auto px-4 py-1.5 text-[11px] font-medium rounded-full
                           text-slate-700 dark:text-zinc-300
                           bg-white dark:bg-zinc-800
                           border border-slate-300 dark:border-zinc-600
                           hover:bg-slate-50 dark:hover:bg-zinc-700
                           shadow-sm shadow-zinc-200/40
                           hover:scale-[1.02] active:scale-[0.97]
                           transition-all duration-150">
                    Close
                </button>
            </div>
        @else
            <div class="p-6 text-center text-[11px] text-slate-500 dark:text-zinc-400">
                Loading...
            </div>
        @endif
    </div>
</div>
