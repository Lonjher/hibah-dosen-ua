@props([
    'name' => null,
    'id' => null,
    'size' => 'md',
    'rounded' => 'full',
    'responsive' => false,
    'maxWidth' => null,
    'placeholder' => null,
    'color' => 'emerald',
    'label' => null,
    'required' => false,
])

@php
    $selectId = $id ?? ($name ?? 'select-' . Str::random(6));

    // ---- Size presets (lebih lega) ----
    $sizes = [
        'sm' => [
            'height'    => 'py-0.5',
            'padding'   => 'pl-2 pr-6',
            'text'      => 'text-[10px]',
            'chevron'   => 'size-2.5 right-1.5',
            'item_py'   => 'py-1',
            'item_px'   => 'px-2.5',
            'item_text' => 'text-[10px]',
            'drop_p'    => 'p-1',
            'drop_min'  => 'min-w-[10rem]',
        ],
        'md' => [
            'height'    => 'py-1',
            'padding'   => 'pl-2 pr-6',
            'text'      => 'text-[10.5px]',
            'chevron'   => 'size-3 right-2',
            'item_py'   => 'py-1.5',
            'item_px'   => 'px-3',
            'item_text' => 'text-[11px]',
            'drop_p'    => 'p-1.5',
            'drop_min'  => 'min-w-[11rem]',
        ],
        'lg' => [
            'height'    => 'py-1.5',
            'padding'   => 'pl-2.5 pr-7',
            'text'      => 'text-[11.5px]',
            'chevron'   => 'size-3.5 right-2.5',
            'item_py'   => 'py-2',
            'item_px'   => 'px-3.5',
            'item_text' => 'text-[12px]',
            'drop_p'    => 'p-2',
            'drop_min'  => 'min-w-[12rem]',
        ],
    ];
    $s = $sizes[$size] ?? $sizes['md'];

    // ---- Rounded ----
    $roundeds = [
        'full' => 'rounded-full',
        'xl'   => 'rounded-xl',
        'lg'   => 'rounded-lg',
        'md'   => 'rounded-md',
        'sm'   => 'rounded-sm',
    ];
    $radius = $roundeds[$rounded] ?? $roundeds['full'];

    // ---- Colors ----
    $colors = [
        'sky'     => ['ring' => 'focus:ring-sky-500/25 focus:border-sky-500',         'chev' => 'group-focus-within:text-sky-500 dark:group-focus-within:text-sky-400'],
        'emerald' => ['ring' => 'focus:ring-emerald-500/25 focus:border-emerald-500', 'chev' => 'group-focus-within:text-emerald-500 dark:group-focus-within:text-emerald-400'],
        'violet'  => ['ring' => 'focus:ring-violet-500/25 focus:border-violet-500',   'chev' => 'group-focus-within:text-violet-500 dark:group-focus-within:text-violet-400'],
        'rose'    => ['ring' => 'focus:ring-rose-500/25 focus:border-rose-500',       'chev' => 'group-focus-within:text-rose-500 dark:group-focus-within:text-rose-400'],
        'amber'   => ['ring' => 'focus:ring-amber-500/25 focus:border-amber-500',     'chev' => 'group-focus-within:text-amber-500 dark:group-focus-within:text-amber-400'],
        'indigo'  => ['ring' => 'focus:ring-indigo-500/25 focus:border-indigo-500',   'chev' => 'group-focus-within:text-indigo-500 dark:group-focus-within:text-indigo-400'],
        'blue'    => ['ring' => 'focus:ring-blue-500/25 focus:border-blue-500',       'chev' => 'group-focus-within:text-blue-500 dark:group-focus-within:text-blue-400'],
        'pink'    => ['ring' => 'focus:ring-pink-500/25 focus:border-pink-500',       'chev' => 'group-focus-within:text-pink-500 dark:group-focus-within:text-pink-400'],
    ];
    $c = $colors[$color] ?? $colors['emerald'];
    $accent = $color;

    // ---- Wrapper ----
    $display     = $responsive ? 'hidden sm:flex' : 'flex';
    $width       = $maxWidth ?? 'w-auto';
    $layoutClass = $label ? 'flex-col gap-1' : '';

    // ---- Trigger ----
    $triggerClass = trim("
        relative w-full {$s['padding']} {$s['height']} {$radius}
        bg-white dark:bg-zinc-800
        text-slate-700 dark:text-zinc-200
        {$s['text']} font-body
        border border-slate-200 dark:border-zinc-700
        shadow-sm shadow-slate-200/60 dark:shadow-zinc-950/40
        hover:shadow-sm hover:shadow-{$accent}-500/20 dark:hover:shadow-{$accent}-500/20
        hover:border-slate-300 dark:hover:border-zinc-600
        focus:outline-none
        focus:ring-2 {$c['ring']}
        focus:shadow-md
        cursor-pointer text-start
        transition-all duration-200 ease-out
        flex items-center gap-1
    ");
@endphp

<div x-data="{
    open: false,
    value: null,
    placeholder: @js($placeholder ?? ''),
    items: [],
    groups: [],
    highlighted: null,
    position: { top: 0, left: 0, minWidth: 0, maxWidth: 0, dropUp: false },
    _observer: null,
    _repositionHandler: null,

    init() {
        this.$nextTick(() => this.sync());
        const sel = this.$refs.native;
        sel.addEventListener('change', () => this.sync());
        this._observer = new MutationObserver(() => this.sync());
        this._observer.observe(sel, { childList: true, subtree: true, characterData: true });
    },
    destroy() {
        if (this._observer) this._observer.disconnect();
        this._detachReposition();
    },

    sync() {
        const sel = this.$refs.native;
        this.value = sel.value;
        const items = [], groups = [];
        Array.from(sel.children).forEach(child => {
            if (child.tagName === 'OPTGROUP') {
                const opts = [];
                Array.from(child.children).forEach(opt => {
                    if (opt.classList.contains('placeholder')) return;
                    opts.push({
                        value: opt.value,
                        label: opt.textContent.trim(),
                        disabled: opt.disabled,
                    });
                });
                groups.push({ label: child.label, options: opts });
            } else if (child.tagName === 'OPTION') {
                if (child.classList.contains('placeholder')) return;
                items.push({
                    value: child.value,
                    label: child.textContent.trim(),
                    disabled: child.disabled,
                });
            }
        });
        this.items = items;
        this.groups = groups;
    },

    allOptions() {
        return [...this.items, ...this.groups.flatMap(g => g.options)];
    },
    displayLabel() {
        const found = this.allOptions().find(o => String(o.value) === String(this.value));
        return found ? found.label : this.placeholder;
    },
    hasValue() {
        return this.value !== '' && this.value !== null && this.value !== undefined;
    },
    isSelected(v) { return String(this.value) === String(v); },
    isHighlighted(v) { return this.highlighted !== null && String(this.highlighted) === String(v); },

    updatePosition() {
        const trigger = this.$refs.trigger;
        if (!trigger) return;
        const rect = trigger.getBoundingClientRect();
        const spaceBelow = window.innerHeight - rect.bottom;
        const dropUp = spaceBelow < 260 && rect.top > spaceBelow;

        // Lebar: minimal = lebar trigger, maksimal = viewport - 16px
        const minWidth = rect.width;
        const maxWidth = Math.min(window.innerWidth - 16, 320);

        this.position = {
            top: dropUp ? rect.top - 6 : rect.bottom + 6,
            left: rect.left,
            minWidth,
            maxWidth,
            dropUp,
        };
    },
    _attachReposition() {
        this._repositionHandler = () => this.updatePosition();
        window.addEventListener('scroll', this._repositionHandler, true);
        window.addEventListener('resize', this._repositionHandler);
    },
    _detachReposition() {
        if (this._repositionHandler) {
            window.removeEventListener('scroll', this._repositionHandler, true);
            window.removeEventListener('resize', this._repositionHandler);
            this._repositionHandler = null;
        }
    },

    toggle() {
        this.open = !this.open;
        if (this.open) {
            this.sync();
            const opts = this.allOptions();
            const current = opts.find(o => String(o.value) === String(this.value));
            this.highlighted = current ? current.value : (opts[0]?.value ?? null);
            this.$nextTick(() => {
                this.updatePosition();
                this._attachReposition();
            });
        } else {
            this._detachReposition();
        }
    },
    close() {
        this.open = false;
        this._detachReposition();
    },
    isInside(el) {
        return this.$refs.trigger?.contains(el) || this.$refs.dropdown?.contains(el);
    },
    choose(opt) {
        if (!opt || opt.disabled) return;
        const sel = this.$refs.native;
        sel.value = opt.value;
        sel.dispatchEvent(new Event('change', { bubbles: true }));
        this.value = opt.value;
        this.close();
    },
    moveHighlight(dir) {
        const opts = this.allOptions().filter(o => !o.disabled);
        if (!opts.length) return;
        const idx = opts.findIndex(o => String(o.value) === String(this.highlighted));
        const next = idx < 0 ? 0 : (idx + dir + opts.length) % opts.length;
        this.highlighted = opts[next].value;
    },
    pickHighlighted() {
        const opt = this.allOptions().find(o => String(o.value) === String(this.highlighted));
        if (opt && !opt.disabled) this.choose(opt);
    }
}"
x-init="init()" x-destroy="destroy()"
@click.window="if (open && !isInside($event.target)) close()"
@keydown.escape="close()"
@keydown.arrow-down.prevent="open && moveHighlight(1)"
@keydown.arrow-up.prevent="open && moveHighlight(-1)"
@keydown.enter.prevent="open && pickHighlighted()"
{{ $attributes->only('class')->merge(['class' => "{$display} {$layoutClass} {$width} group relative"]) }}>

    @if ($label)
        <label for="{{ $selectId }}" class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300">
            {{ $label }} @if ($required)<span class="text-rose-500">*</span>@endif
        </label>
    @endif

    {{-- Native select --}}
    <select x-ref="native" id="{{ $selectId }}" @if ($name) name="{{ $name }}" @endif
        class="sr-only" tabindex="-1" aria-hidden="true" {{ $attributes->except('class') }}>
        @if ($placeholder)
            <option value="" class="placeholder" selected disabled>{{ $placeholder }}</option>
        @endif
        {{ $slot }}
    </select>

    {{-- Trigger --}}
    <div class="relative w-full">
        <button type="button" x-ref="trigger" @click="toggle()" :aria-expanded="open" aria-haspopup="listbox"
            class="{{ $triggerClass }}">
            <span x-text="displayLabel()" :class="!hasValue() && 'text-slate-400 dark:text-zinc-500'"
                class="truncate flex-1 min-w-0"></span>
        </button>

        <flux:icon.chevron-down
            class="{{ $s['chevron'] }} absolute top-1/2 -translate-y-1/2
                   text-slate-400 dark:text-zinc-500
                   pointer-events-none
                   {{ $c['chev'] }}
                   transition-transform duration-300 ease-out"
            x-bind:class="open && 'rotate-180'" />
    </div>

    <template x-teleport="body">
        <div x-ref="dropdown" x-show="open" x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-[0.96] -translate-y-1"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-[0.96] -translate-y-1"
            :style="`position: fixed; top: ${position.top}px; left: ${position.left}px; min-width: ${position.minWidth}px; max-width: ${position.maxWidth}px;`"
            x-bind:class="position.dropUp && '-translate-y-full'"
            class="z-[9999] w-max
                   max-h-[min(20rem,60vh)] overflow-y-auto
                   {{ $s['drop_p'] }}
                   rounded-2xl
                   bg-white/85 dark:bg-zinc-900/85
                   backdrop-blur-2xl backdrop-saturate-200
                   border border-white/60 dark:border-white/10
                   ring-1 ring-inset ring-white/40 dark:ring-white/5
                   shadow-xl shadow-slate-900/15 dark:shadow-black/50
                   origin-top
                   [scrollbar-width:thin]
                   [&::-webkit-scrollbar]:w-1.5
                   [&::-webkit-scrollbar-thumb]:bg-slate-300/70
                   [&::-webkit-scrollbar-thumb]:rounded-full
                   dark:[&::-webkit-scrollbar-thumb]:bg-zinc-700/70"
            role="listbox">

            {{-- Standalone options --}}
            <template x-for="(opt, idx) in items" :key="'i-' + opt.value">
                <button type="button" role="option"
                    :aria-selected="isSelected(opt.value)"
                    :disabled="opt.disabled"
                    @click="choose(opt)"
                    @mouseenter="highlighted = opt.value"
                    :style="`animation-delay: ${idx * 20}ms`"
                    class="select-item
                           group/item flex items-center w-full gap-2
                           {{ $s['item_py'] }} {{ $s['item_px'] }}
                           rounded-full
                           text-start {{ $s['item_text'] }} font-medium leading-none
                           text-slate-700 dark:text-zinc-200
                           transition-all duration-150 ease-out
                           will-change-transform
                           disabled:opacity-50 disabled:cursor-not-allowed"
                    :class="{
                        'bg-{{ $accent }}-500/15 text-{{ $accent }}-700 dark:bg-{{ $accent }}-500/20 dark:text-{{ $accent }}-200':
                            isSelected(opt.value),
                        'bg-white/70 dark:bg-white/10 ring-1 ring-inset ring-white/60 dark:ring-white/15 translate-x-0.5 shadow-lg shadow-{{ $accent }}-500/30 dark:shadow-{{ $accent }}-400/20':
                            isHighlighted(opt.value) && !isSelected(opt.value),
                    }">
                    <span class="w-3.5 flex-shrink-0 flex items-center justify-center
                                 transition-all duration-200 ease-out"
                        :class="isSelected(opt.value)
                            ? 'opacity-100 scale-100 rotate-0'
                            : 'opacity-0 scale-50 -rotate-12'">
                        <flux:icon.check class="size-3 text-{{ $accent }}-600 dark:text-{{ $accent }}-400" />
                    </span>

                    <span class="truncate min-w-0 flex-1 transition-transform duration-150 ease-out
                                 group-hover/item:translate-x-0.5"
                        x-text="opt.label"></span>
                </button>
            </template>

            {{-- Grouped options --}}
            <template x-for="(group, gi) in groups" :key="'g-' + gi">
                <div :class="gi > 0 && 'mt-1 pt-1 border-t border-slate-200/60 dark:border-white/5'">
                    <div :style="`animation-delay: ${gi * 30}ms`"
                        class="select-item px-2.5 pt-1.5 pb-0.5 text-[9px] font-semibold uppercase tracking-[0.08em]
                               text-slate-500 dark:text-zinc-400"
                        x-text="group.label"></div>

                    <template x-for="(opt, oi) in group.options" :key="'g-' + gi + '-' + opt.value">
                        <button type="button" role="option"
                            :aria-selected="isSelected(opt.value)"
                            :disabled="opt.disabled"
                            @click="choose(opt)"
                            @mouseenter="highlighted = opt.value"
                            :style="`animation-delay: ${(gi * 30) + (oi * 20)}ms`"
                            class="select-item
                                   group/item flex items-center w-full gap-2
                                   {{ $s['item_py'] }} {{ $s['item_px'] }}
                                   rounded-full
                                   text-start {{ $s['item_text'] }} font-medium leading-none
                                   text-slate-700 dark:text-zinc-200
                                   transition-all duration-150 ease-out
                                   will-change-transform
                                   disabled:opacity-50 disabled:cursor-not-allowed"
                            :class="{
                                'bg-{{ $accent }}-500/15 text-{{ $accent }}-700 dark:bg-{{ $accent }}-500/20 dark:text-{{ $accent }}-200':
                                    isSelected(opt.value),
                                'bg-white/70 dark:bg-white/10 ring-1 ring-inset ring-white/60 dark:ring-white/15 translate-x-0.5 shadow-lg shadow-{{ $accent }}-500/30 dark:shadow-{{ $accent }}-400/20':
                                    isHighlighted(opt.value) && !isSelected(opt.value),
                            }">
                            <span class="w-3.5 flex-shrink-0 flex items-center justify-center
                                         transition-all duration-200 ease-out"
                                :class="isSelected(opt.value)
                                    ? 'opacity-100 scale-100 rotate-0'
                                    : 'opacity-0 scale-50 -rotate-12'">
                                <flux:icon.check class="size-3 text-{{ $accent }}-600 dark:text-{{ $accent }}-400" />
                            </span>

                            <span class="truncate min-w-0 flex-1 transition-transform duration-150 ease-out
                                         group-hover/item:translate-x-0.5"
                                x-text="opt.label"></span>
                        </button>
                    </template>
                </div>
            </template>

            {{-- Empty state --}}
            <template x-if="items.length === 0 && groups.length === 0">
                <div class="px-3 py-2 text-[11px] text-zinc-400 dark:text-zinc-500 italic">
                    No option provided
                </div>
            </template>
        </div>
    </template>
</div>
