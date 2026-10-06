<?php

use App\Models\Informations;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    /** Number of items shown (default: 3 latest informations) */
    public int $limit = 3;

    public bool $importantOnly = false;
    public bool $dismissible = true;
    public string $storageKey = 'dismissed_informations';
    public int $refreshInterval = 60;
    public bool $showEmpty = true;

    /** Truncate content (characters). 0 = show all */
    public int $contentLimit = 140;

    #[Computed]
    public function informations()
    {
        return Informations::query()
            ->visible()
            ->with('author')
            ->when($this->importantOnly, fn ($q) => $q->whereIn('type', ['warning', 'danger']))
            ->ordered()
            ->limit($this->limit)
            ->get();
    }
};
?>

<div
    @if ($refreshInterval > 0) wire:poll.{{ $refreshInterval }}s @endif
    x-data="{
        dismissed: JSON.parse(localStorage.getItem(@js($storageKey)) || '[]'),
        isDismissed(id) { return this.dismissed.includes(id); },
        dismiss(id) {
            this.dismissed.push(id);
            localStorage.setItem(@js($storageKey), JSON.stringify(this.dismissed));
        }
    }"
    class="w-full"
>
    @if ($this->informations->isNotEmpty())

        {{-- ══════════ HEADER ══════════ --}}
        <div class="flex items-center justify-between gap-2 mb-2.5">
            <div class="flex items-center gap-1.5 min-w-0">
                <div class="w-6 h-6 rounded-full bg-sky-100 dark:bg-sky-900/40
                            flex items-center justify-center shrink-0">
                    <flux:icon.megaphone class="size-3 text-sky-600 dark:text-sky-400" />
                </div>
                <h2 class="text-[11px] font-semibold tracking-wide uppercase
                           text-slate-700 dark:text-zinc-300 truncate">
                    Information
                </h2>
                <span class="inline-flex items-center justify-center
                             min-w-[18px] h-[18px] px-1.5 rounded-full
                             bg-sky-100 text-sky-700 text-[9px] font-bold
                             dark:bg-sky-900/40 dark:text-sky-300 shrink-0">
                    {{ $this->informations->count() }}
                </span>
            </div>
        </div>

        {{-- ══════════ LIST ══════════ --}}
        <div class="space-y-1.5">
            @foreach ($this->informations as $info)
                @php
                    $type = $info->typeMeta();
                    $body = $this->contentLimit > 0
                        ? \Illuminate\Support\Str::limit(strip_tags($info->content), $this->contentLimit)
                        : $info->content;
                @endphp

                <article
                    x-show="! isDismissed({{ $info->id }})"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0 -translate-x-2"
                    class="group relative rounded-2xl
                           border {{ $type['border'] }} {{ $type['bg'] }}
                           p-2.5 sm:p-3
                           hover:shadow-sm hover:-translate-y-px
                           transition-all duration-150">

                    <div class="flex items-start gap-2.5">

                        {{-- Icon --}}
                        <div class="shrink-0 w-6 h-6 rounded-full
                                    bg-white/70 dark:bg-zinc-900/50
                                    flex items-center justify-center
                                    ring-1 ring-white/50 dark:ring-zinc-800/50">
                            <flux:icon :name="$type['icon']" class="size-3 {{ $type['icon_class'] }}" />
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">

                            {{-- Title row --}}
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="text-[11px] font-semibold leading-snug
                                           {{ $type['text'] }} line-clamp-2">
                                    {{ $info->title }}
                                </h3>

                                @if ($dismissible)
                                    <button type="button"
                                        x-on:click="dismiss({{ $info->id }})"
                                        aria-label="Dismiss information"
                                        class="shrink-0 -mt-0.5 -mr-0.5 w-5 h-5 rounded-full
                                               flex items-center justify-center
                                               {{ $type['text'] }}
                                               opacity-0 group-hover:opacity-70
                                               hover:!opacity-100 hover:bg-white/60 dark:hover:bg-zinc-800/60
                                               hover:scale-110 active:scale-95
                                               transition-all duration-150">
                                        <flux:icon.x-mark class="size-3" />
                                    </button>
                                @endif
                            </div>

                            {{-- Body --}}
                            <p class="text-[10px] leading-relaxed
                                      {{ $type['text'] }} opacity-80">
                                {{ $body }}
                            </p>

                            {{-- Footer meta --}}
                            <div class="mt-1.5 flex items-center gap-1.5 flex-wrap
                                        text-[9px] {{ $type['text'] }} opacity-60">
                                <span class="font-medium">{{ $info->author?->full_name ?? 'Admin' }}</span>
                                <span class="w-0.5 h-0.5 rounded-full bg-current"></span>
                                <span>{{ $info->published_at?->diffForHumans(short: true) ?? '—' }}</span>
                                @if ($info->expires_at && $info->expires_at->isFuture())
                                    <span class="w-0.5 h-0.5 rounded-full bg-current"></span>
                                    <span class="inline-flex items-center gap-0.5">
                                        <flux:icon.clock class="size-2.5" />
                                        {{ $info->expires_at->diffForHumans(short: true) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

    @elseif ($showEmpty)
        {{-- ══════════ EMPTY STATE ══════════ --}}
        <div class="rounded-2xl border border-dashed border-slate-300 dark:border-zinc-700
                    bg-slate-50/60 dark:bg-zinc-900/40
                    px-3 py-5 text-center">
            <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-zinc-800
                        flex items-center justify-center mx-auto">
                <flux:icon.megaphone class="size-5 text-slate-300 dark:text-zinc-600" />
            </div>
            <p class="mt-2 text-[10.5px] font-medium text-slate-500 dark:text-zinc-400">
                No information yet
            </p>
            @if ($importantOnly)
                <p class="mt-0.5 text-[9.5px] text-slate-400 dark:text-zinc-500">
                    No important information at the moment
                </p>
            @endif
        </div>
    @endif
</div>
