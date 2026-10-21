@props([
    'name'        => null,
    'id'          => null,
    'type'        => 'text',
    'size'        => 'md',
    'rounded'     => 'full',
    'placeholder' => null,
    'label'       => null,
    'required'    => false,
    'hint'        => null,
    'icon'        => null,
    'value'       => null,
    'passwordToggle' => null, // null = auto-detect dari type
])

@php
    $inputId = $id ?? $name ?? 'input-' . \Illuminate\Support\Str::random(6);

    $sizes = [
        'sm' => ['height' => 'py-1',   'padding' => 'px-2.5', 'text' => 'text-[10px]',   'icon' => 'size-3',   'iconLeft' => 'left-2',   'paddingL' => 'pl-7'],
        'md' => ['height' => 'py-1.5', 'padding' => 'px-2.5', 'text' => 'text-[11px]',   'icon' => 'size-3.5', 'iconLeft' => 'left-2.5', 'paddingL' => 'pl-8'],
        'lg' => ['height' => 'py-2',   'padding' => 'px-3',   'text' => 'text-[12px]',   'icon' => 'size-4',   'iconLeft' => 'left-3',   'paddingL' => 'pl-9'],
    ];
    $s = $sizes[$size] ?? $sizes['md'];

    $roundeds = ['full' => 'rounded-full', 'lg' => 'rounded-lg', 'md' => 'rounded-md'];
    $radius = $roundeds[$rounded] ?? $roundeds['md'];

    $isFile = $type === 'file';
    $isPassword = $type === 'password';
    $showToggle = $passwordToggle ?? $isPassword;
    $effectiveToggle = $isPassword && $showToggle;
    $layoutClass = $label ? 'flex flex-col gap-1' : '';

    // Ambil wire:model key untuk binding manual
    $wireModelKey = collect($attributes->getAttributes())
        ->first(fn ($value, $key) => str_starts_with($key, 'wire:model'));

    // Buang wire:model dari attributes input (kita handle manual via $wire.upload)
    $inputAttributes = $isFile
        ? $attributes->except(['class'])->filter(fn ($value, $key) => ! str_starts_with($key, 'wire:model'))
        : $attributes->except('class');

    $inputClass = $isFile
        ? trim("
            block w-full text-[11px] px-2.5 py-1.5 {$radius}
            file:mr-2.5 file:py-1 file:px-2.5 file:rounded-full file:border-0
            file:text-[10.5px] file:font-medium
            file:bg-violet-100 file:text-violet-700
            dark:file:bg-violet-900/40 dark:file:text-violet-300
            hover:file:bg-violet-200 dark:hover:file:bg-violet-900/60
            file:hover:scale-105 file:active:scale-95
            file:transition-all file:duration-150

            bg-white dark:bg-zinc-800
            text-slate-900 dark:text-zinc-100

            border border-slate-200 dark:border-zinc-700

            shadow-sm shadow-slate-200/60 dark:shadow-zinc-950/40
            hover:shadow-sm hover:shadow-violet-500/20 dark:hover:shadow-violet-500/20
            hover:border-slate-300 dark:hover:border-zinc-600

            focus:outline-none
            focus:border-violet-500 dark:focus:border-violet-500
            focus:ring-2 focus:ring-violet-500/25 dark:focus:ring-violet-500/30

            disabled:opacity-60 disabled:cursor-wait

            transition-all duration-200
        ")
        : trim("
            block w-full {$s['padding']} {$s['height']} {$radius}
            " . ($icon ? "{$s['paddingL']}" : '') . "
            " . ($effectiveToggle ? 'pr-9' : '') . "
            bg-white dark:bg-zinc-800
            text-slate-900 dark:text-zinc-100
            placeholder:text-slate-400 dark:placeholder:text-zinc-500
            {$s['text']} font-body

            border border-slate-200 dark:border-zinc-700

            shadow-sm shadow-slate-200/60 dark:shadow-zinc-950/40
            hover:shadow-sm hover:shadow-emerald-500/20 dark:hover:shadow-emerald-500/20
            hover:border-slate-300 dark:hover:border-zinc-600

            focus:outline-none
            focus:bg-white dark:focus:bg-zinc-900
            focus:border-emerald-500 dark:focus:border-emerald-500
            focus:ring-2 focus:ring-emerald-500/25 dark:focus:ring-emerald-500/30
            focus:shadow-md focus:shadow-emerald-500/10

            transition-all duration-200
        ");
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'w-full group ' . $layoutClass]) }}>

    @if ($label)
        <label for="{{ $inputId }}"
            class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300">
            {{ $label }} @if ($required)<span class="text-rose-500">*</span>@endif
        </label>
    @endif

    <div class="relative w-full"
        @if ($isFile)
            x-data="{
                state: 'idle',   /* idle | uploading | uploaded | error */
                progress: 0,
                fileName: '',
                fileSize: '',
                wireModelKey: @js($wireModelKey),
                isUploading: false,

                formatSize(bytes) {
                    if (bytes < 1024) return bytes + ' B';
                    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
                    return (bytes / 1024 / 1024).toFixed(2) + ' MB';
                },

                onFileSelected(event) {
                    const file = event.target.files[0];

                    if (! file) {
                        this.resetState();
                        return;
                    }

                    this.fileName = file.name;
                    this.fileSize = this.formatSize(file.size);
                    this.state = 'uploading';
                    this.progress = 0;
                    this.isUploading = true;

                    // Upload manual via $wire.upload → callback pasti jalan
                    $wire.upload(
                        this.wireModelKey,
                        file,
                        /* finish */
                        () => {
                            this.isUploading = false;
                            this.state = 'uploaded';
                            this.progress = 100;
                        },
                        /* error */
                        () => {
                            this.isUploading = false;
                            this.state = 'error';
                            this.progress = 0;
                        },
                        /* progress */
                        (event) => {
                            this.progress = event.detail.progress;
                        },
                        /* cancelled */
                        () => {
                            this.isUploading = false;
                            this.resetState();
                        }
                    );
                },

                resetState() {
                    this.fileName = '';
                    this.fileSize = '';
                    this.progress = 0;
                    this.state = 'idle';
                },

                clearFile() {
                    this.$refs.fileInput.value = '';
                    this.resetState();
                    if (this.wireModelKey) {
                        $wire.set(this.wireModelKey, null, false);
                    }
                }
            }"
        @elseif ($effectiveToggle)
            x-data="{ showPassword: false }"
        @endif
    >
        @if ($icon && ! $isFile)
            <flux:icon :name="$icon"
                class="{{ $s['icon'] }} absolute {{ $s['iconLeft'] }} top-1/2 -translate-y-1/2
                       text-slate-400 dark:text-zinc-500
                       pointer-events-none
                       group-focus-within:text-emerald-500 dark:group-focus-within:text-emerald-400
                       transition-colors" />
        @endif

        <input
            @if ($effectiveToggle)
                :type="showPassword ? 'text' : 'password'"
            @else
                type="{{ $type }}"
            @endif
            id="{{ $inputId }}"
            @if ($name) name="{{ $name }}" @endif
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @if ($value !== null) value="{{ $value }}" @endif

            @if ($isFile)
                x-ref="fileInput"
                x-on:change="onFileSelected($event)"
            @endif

            {{ $inputAttributes->merge(['class' => $inputClass]) }} />

        @if ($effectiveToggle)
            {{-- ══════════ PASSWORD TOGGLE ══════════ --}}
            <button type="button"
                x-on:click="showPassword = !showPassword"
                :aria-pressed="showPassword"
                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                class="absolute inset-y-0 right-0 flex items-center pr-2.5
                       text-slate-400 hover:text-emerald-600
                       dark:text-zinc-500 dark:hover:text-emerald-400
                       transition-colors duration-150
                       focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/40 rounded-full">

                {{-- Eye (hidden state) --}}
                <svg x-show="!showPassword" x-cloak class="{{ $s['icon'] }}"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>

                {{-- Eye-slash (shown state) --}}
                <svg x-show="showPassword" x-cloak class="{{ $s['icon'] }}"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                </svg>
            </button>
        @endif

        @if ($isFile)
            {{-- ══════════ SINGLE INDICATOR (state-aware) ══════════ --}}
            <div x-show="fileName || state === 'error'" x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="mt-2 flex items-center gap-2 px-2.5 py-1.5 rounded-full border
                       transition-colors duration-200"
                :class="{
                    'bg-violet-50 dark:bg-violet-900/20 border-violet-200 dark:border-violet-800/60': state === 'uploading' || state === 'idle',
                    'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800/60': state === 'uploaded',
                    'bg-rose-50 dark:bg-rose-900/20 border-rose-200 dark:border-rose-800/60': state === 'error',
                }">

                {{-- ─────── ICON (spinner → check → warning) ─────── --}}
                <div class="w-6 h-6 rounded-full shrink-0 flex items-center justify-center transition-colors duration-200"
                    :class="{
                        'bg-violet-100 dark:bg-violet-900/40': state === 'uploading' || state === 'idle',
                        'bg-emerald-100 dark:bg-emerald-900/40': state === 'uploaded',
                        'bg-rose-100 dark:bg-rose-900/40': state === 'error',
                    }">

                    {{-- Spinner (uploading / idle) --}}
                    <template x-if="state === 'uploading' || state === 'idle'">
                        <svg class="animate-spin size-3 text-violet-600 dark:text-violet-400"
                            viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/>
                            <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                        </svg>
                    </template>

                    {{-- Check (uploaded) --}}
                    <template x-if="state === 'uploaded'">
                        <svg class="size-3 text-emerald-600 dark:text-emerald-400"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12" />
                        </svg>
                    </template>

                    {{-- Warning (error) --}}
                    <template x-if="state === 'error'">
                        <flux:icon.exclamation-triangle class="size-3 text-rose-600 dark:text-rose-400" />
                    </template>
                </div>

                {{-- ─────── TEXT + PROGRESS ─────── --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[10.5px] font-semibold truncate transition-colors duration-200"
                            :class="{
                                'text-violet-700 dark:text-violet-300': state === 'uploading' || state === 'idle',
                                'text-emerald-800 dark:text-emerald-300': state === 'uploaded',
                                'text-rose-700 dark:text-rose-300': state === 'error',
                            }"
                            x-text="state === 'error' ? 'Upload failed — try again' : fileName">
                        </span>

                        <span x-show="state === 'uploading'"
                            class="text-[10px] font-bold shrink-0 tabular-nums
                                   text-violet-700 dark:text-violet-300"
                            x-text="progress + '%'"></span>

                        <span x-show="state === 'uploaded'"
                            class="text-[9px] font-medium shrink-0
                                   text-emerald-600 dark:text-emerald-400"
                            x-text="fileSize"></span>
                    </div>

                    <div x-show="state === 'uploading'" x-cloak
                        class="mt-1 h-1 rounded-full bg-violet-200 dark:bg-violet-800 overflow-hidden">
                        <div class="h-full rounded-full bg-violet-500 dark:bg-violet-400
                                    transition-all duration-200 ease-out"
                            :style="'width: ' + progress + '%'"></div>
                    </div>
                </div>

                <button type="button"
                    x-show="state === 'uploaded'" x-cloak
                    x-on:click="clearFile()"
                    aria-label="Remove file"
                    class="shrink-0 w-5 h-5 rounded-full flex items-center justify-center
                           text-emerald-600 dark:text-emerald-400
                           hover:bg-emerald-100 dark:hover:bg-emerald-900/40
                           hover:scale-110 active:scale-95
                           transition-all duration-150">
                    <flux:icon.x-mark class="size-3" />
                </button>
            </div>
        @endif
    </div>

    @if ($hint)
        <p class="mt-0.5 text-[9.5px] text-slate-400 dark:text-zinc-500">{{ $hint }}</p>
    @endif
</div>
