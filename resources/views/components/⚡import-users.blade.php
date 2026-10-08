<?php

use App\Services\UserImportService;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

new class extends Component {
    use WithFileUploads;

    public $file;
    public array $rows = [];
    public array $result = [];
    public bool $show = false;
    public bool $imported = false;
    public bool $reviewer = true;

    /* ─── Event listeners ─── */

    #[On('open-import-reviewer')]
    public function openReviewer(): void
    {
        $this->open(true);
    }

    #[On('open-import-user')]
    public function openUser(): void
    {
        $this->open(false);
    }

    #[Computed]
    public function columnGuide(): array
    {
        return $this->service()->columnGuide();
    }

    public function open(bool $reviewer): void
    {
        $this->reset(['file', 'rows', 'result', 'imported']);
        $this->resetErrorBag();
        $this->resetValidation();
        $this->reviewer = $reviewer;
        $this->show = true;
    }

    public function close(): void
    {
        $this->reset(['file', 'rows', 'result', 'imported']);
        $this->resetValidation();
        $this->show = false;
    }

    /* ─── Actions ─── */
    public function downloadTemplate()
    {
        $spreadsheet = $this->service()->buildTemplate();
        $writer = new XlsxWriter($spreadsheet);

        $filename = $this->reviewer ? 'template-reviewer.xlsx' : 'template-user.xlsx';

        return response()->streamDownload(
            fn () => $writer->save('php://output'),
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    public function updatedFile(): void
    {
        $this->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:4096',
        ]);

        $outcome = $this->service()->parseAndValidate($this->file);

        $this->rows = $outcome['rows'];
        $this->imported = false;
        $this->result = [];

        if ($outcome['error']) {
            $this->addError('file', $outcome['error']);
        }
    }

    public function import(): void
    {
        try {
            $this->result = $this->service()->import($this->rows);
            $this->imported = true;

            $created = $this->result['created'];
            $updated = $this->result['updated'];

            if ($created > 0 || $updated > 0) {
                $parts = [];
                if ($created > 0) $parts[] = "{$created} created";
                if ($updated > 0) $parts[] = "{$updated} updated";

                Flux::toast('Import done: ' . implode(', ', $parts) . '.', variant: 'success');
                $this->dispatch($this->reviewer ? 'reviewer-imported' : 'user-imported');
            }
        } catch (\Throwable $e) {
            report($e);
            Flux::toast($e->getMessage(), variant: 'danger');
        }
    }

    public function resetUpload(): void
    {
        $this->reset(['file', 'rows', 'result', 'imported']);
        $this->resetValidation();
    }

    /* ─── Helper ─── */
    protected function service(): UserImportService
    {
        return new UserImportService($this->reviewer ? 'REVIEWER' : 'USER');
    }
};
?>

<div x-data x-show="$wire.show" x-transition.opacity x-cloak x-on:keydown.escape.window="$wire.close()"
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4 font-sans"
    @click.self="$wire.close()">

    <div class="flex max-h-[92vh] w-full sm:max-w-2xl flex-col overflow-hidden
                bg-white dark:bg-zinc-900 rounded-t-3xl sm:rounded-3xl shadow-2xl
                border border-slate-200 dark:border-zinc-700"
        @click.stop>

        {{-- HEADER --}}
        <div class="shrink-0 bg-gradient-to-r from-emerald-600 to-emerald-500 px-5 py-3.5">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                        <flux:icon.arrow-down-on-square class="size-4 text-white" />
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-base font-semibold text-white leading-tight">
                            {{ __("Import ") }} {{ $reviewer ? 'Reviewers' : 'Users' }}
                        </h3>
                        <p class="text-[12px] text-white/80 mt-0.5 leading-tight">
                            {{ __("Upload an Excel file (.xlsx / .xls) to bulk-add or update") . $reviewer ? 'reviewers' : 'users' }}
                        </p>
                    </div>
                </div>
                <button type="button" wire:click="close" aria-label="Close"
                    class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center
                           text-white/80 hover:text-white hover:bg-white/10 transition-colors">
                    <flux:icon.x-mark class="size-4" />
                </button>
            </div>
        </div>

        {{-- BODY --}}
        <div class="flex-1 overflow-y-auto px-5 py-4 space-y-4">

            @if (empty($rows) && ! $imported)
                {{-- Section: Before you start --}}
                <section class="space-y-3">
                    <header class="flex items-center gap-2">
                        <flux:icon.document-arrow-up class="size-3.5 text-emerald-600 dark:text-emerald-400" />
                        <h4 class="text-[11px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400">
                            {{ __("Before you start") }}
                        </h4>
                        <div class="flex-1 h-px bg-slate-200 dark:bg-zinc-700"></div>
                    </header>

                    {{-- Expandable: Download Template --}}
                    <div x-data="{ open: false }"
                        class="rounded-2xl overflow-hidden
                               border border-emerald-200 dark:border-emerald-800/60
                               bg-emerald-50/60 dark:bg-emerald-900/15">

                        <button type="button" @click="open = ! open"
                            class="w-full flex items-center gap-2.5 px-4 py-2.5
                                   hover:bg-emerald-100/60 dark:hover:bg-emerald-900/25
                                   transition-colors text-left">
                            <div class="w-7 h-7 rounded-full bg-emerald-600/15 dark:bg-emerald-500/20
                                        flex items-center justify-center shrink-0">
                                <flux:icon.arrow-down-tray class="size-3.5 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <span class="flex-1 text-[12px] font-semibold
                                         text-emerald-800 dark:text-emerald-300">
                                {{ __("Download Template Excel") }}
                            </span>
                            <flux:icon.chevron-down
                                class="size-3.5 text-emerald-600 dark:text-emerald-400 transition-transform"
                                x-bind:class="open ? 'rotate-180' : ''" />
                        </button>

                        <div x-show="open" x-collapse class="px-4 pb-4 pt-0.5 space-y-3">
                            <p class="text-[11.5px] leading-relaxed
                                      text-emerald-700/90 dark:text-emerald-400/90">
                                {{ __("Download this Excel template so the column names and order match what the
                                system expects. You can also prepare your own file as long as the
                                required columns are present." )}}
                            </p>

                            <button type="button" wire:click="downloadTemplate"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full
                                       text-[11.5px] font-medium text-white
                                       bg-emerald-600 hover:bg-emerald-700
                                       shadow-sm shadow-emerald-500/25
                                       hover:scale-[1.02] active:scale-[0.97]
                                       transition-all duration-150">
                                <flux:icon.arrow-down-tray class="size-3.5" />
                                {{ __("Download") }}
                            </button>
                        </div>
                    </div>

                    {{-- Expandable: Column Guide --}}
                    <div x-data="{ open: false }"
                        class="rounded-2xl overflow-hidden
                            border border-sky-200 dark:border-sky-800/60
                            bg-sky-50/60 dark:bg-sky-900/15">

                        <button type="button" @click="open = ! open"
                            class="w-full flex items-center gap-2.5 px-4 py-2.5
                                hover:bg-sky-100/60 dark:hover:bg-sky-900/25
                                transition-colors text-left">
                            <div class="w-7 h-7 rounded-full bg-sky-600/15 dark:bg-sky-500/20
                                        flex items-center justify-center shrink-0">
                                <flux:icon.information-circle class="size-3.5 text-sky-600 dark:text-sky-400" />
                            </div>
                            <span class="flex-1 text-[12px] font-semibold
                                        text-sky-800 dark:text-sky-300">
                                {{ __('Column Guide') }}
                            </span>
                            <span class="shrink-0 px-2 py-0.5 rounded-full
                                        bg-sky-600/15 dark:bg-sky-500/20
                                        text-[10px] font-semibold
                                        text-sky-700 dark:text-sky-400">
                                {{ count($this->columnGuide) }} {{ __('columns') }}
                            </span>
                            <flux:icon.chevron-down class="size-3.5 text-sky-600 dark:text-sky-400 transition-transform"
                                x-bind:class="open ? 'rotate-180' : ''" />
                        </button>

                        <div x-show="open" x-collapse class="px-4 pb-4 pt-0.5">
                            <table class="w-full text-[11.5px]">
                                <thead>
                                    <tr class="text-left text-sky-700/80 dark:text-sky-400/80">
                                        <th class="pb-1.5 font-semibold">{{ __('Column') }}</th>
                                        <th class="pb-1.5 font-semibold w-20 text-center">{{ __('Required') }}</th>
                                        <th class="pb-1.5 font-semibold">{{ __('Notes') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-sky-200/60 dark:divide-sky-800/40
                                            text-sky-900/90 dark:text-sky-200/90">
                                    @foreach ($this->columnGuide as $col)
                                        <tr>
                                            <td class="py-1.5 font-mono font-semibold">{{ $col['column'] }}</td>
                                            <td class="py-1.5 text-center">
                                                @if ($col['required'])
                                                    <span class="inline-block px-2 py-0.5 rounded-full
                                                                bg-rose-100 dark:bg-rose-900/40
                                                                text-rose-700 dark:text-rose-400
                                                                text-[10px] font-semibold">
                                                        {{ __('Required') }}
                                                    </span>
                                                @else
                                                    <span class="inline-block px-2 py-0.5 rounded-full
                                                                bg-slate-100 dark:bg-zinc-800
                                                                text-slate-600 dark:text-zinc-400
                                                                text-[10px] font-semibold">
                                                        {{ __('Optional') }}
                                                    </span>
                                                @endif
                                            </td>
                                            {{-- Notes may contain inline <code> tags — strings are hardcoded in the service, no user input --}}
                                            <td class="py-1.5">{!! __($col['notes']) !!}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <div class="mt-3 p-3 rounded-xl
                                        bg-sky-100/60 dark:bg-sky-900/25
                                        border border-sky-200 dark:border-sky-800/60">
                                <p class="text-[11px] leading-relaxed
                                        text-sky-800 dark:text-sky-300">
                                    <strong>{{ __('Tip') }}:</strong>
                                    {{ __("Column order doesn't have to match the template — what matters is the column names. Extra columns that aren't recognized will simply be ignored.") }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Section: Upload --}}
                <section class="space-y-3">
                    <header class="flex items-center gap-2">
                        <flux:icon.arrow-up-tray class="size-3.5 text-emerald-600 dark:text-emerald-400" />
                        <h4 class="text-[11px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400">
                            {{ __("Upload File") }}
                        </h4>
                        <div class="flex-1 h-px bg-slate-200 dark:bg-zinc-700"></div>
                    </header>

                    <div>
                        <label class="block cursor-pointer">
                            <input type="file" wire:model="file" accept=".xlsx,.xls" class="sr-only" />
                            <div class="flex flex-col items-center gap-2 p-6 rounded-2xl
                                        border-2 border-dashed
                                        border-slate-300 dark:border-zinc-600
                                        hover:border-emerald-500 dark:hover:border-emerald-500
                                        bg-slate-50 dark:bg-zinc-800/40
                                        hover:bg-emerald-50/50 dark:hover:bg-emerald-900/10
                                        transition-colors">

                                <div wire:loading.remove wire:target="file">
                                    <div class="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-900/40
                                                flex items-center justify-center mx-auto">
                                        <flux:icon.arrow-up-tray class="size-5 text-emerald-600 dark:text-emerald-400" />
                                    </div>
                                    <p class="mt-3 text-[13px] font-medium text-slate-700 dark:text-zinc-300 text-center">
                                        {{ __("Click to select an Excel file") }}
                                    </p>
                                    <p class="mt-1 text-[11px] text-slate-500 dark:text-zinc-400 text-center">
                                        {{ __("Max. 4 MB · .xlsx, .xls") }}
                                    </p>
                                </div>

                                <div wire:loading wire:target="file" class="text-center">
                                    <svg class="animate-spin size-6 mx-auto text-emerald-600" viewBox="0 0 24 24" fill="none">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25" />
                                        <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
                                    </svg>
                                    <p class="mt-3 text-[12px] font-medium text-emerald-600">{{ __("Reading file…") }}</p>
                                </div>
                            </div>
                        </label>
                        @error('file')
                            <p class="mt-2 flex items-center gap-1.5 text-[11px] text-rose-600">
                                <flux:icon.exclamation-circle class="size-3 shrink-0" />
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </section>
            @endif

            {{-- Preview --}}
            @if (! empty($rows) && ! $imported)
                @php
                    $validCount = count(array_filter($rows, fn($r) => $r['valid']));
                    $errorCount = count($rows) - $validCount;
                @endphp

                <section class="space-y-3">
                    <header class="flex items-center gap-2">
                        <flux:icon.table-cells class="size-3.5 text-emerald-600 dark:text-emerald-400" />
                        <h4 class="text-[11px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400">
                            {{ __("Preview") }}
                        </h4>
                        <div class="flex-1 h-px bg-slate-200 dark:bg-zinc-700"></div>
                    </header>

                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                                     bg-slate-100 dark:bg-zinc-800
                                     text-[11px] font-medium text-slate-700 dark:text-zinc-300">
                            <flux:icon.queue-list class="size-3" />
                            Total: {{ count($rows) }}
                        </span>
                        @if ($validCount > 0)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                                         bg-emerald-100 dark:bg-emerald-900/30
                                         text-[11px] font-medium text-emerald-700 dark:text-emerald-400">
                                <flux:icon.check-circle class="size-3" />
                                Valid: {{ $validCount }}
                            </span>
                        @endif
                        @if ($errorCount > 0)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                                         bg-rose-100 dark:bg-rose-900/30
                                         text-[11px] font-medium text-rose-700 dark:text-rose-400">
                                <flux:icon.exclamation-circle class="size-3" />
                                Error: {{ $errorCount }}
                            </span>
                        @endif
                    </div>

                    <div class="rounded-2xl border border-slate-200 dark:border-zinc-700 overflow-hidden">
                        <div class="max-h-80 overflow-auto">
                            <table class="w-full text-[11.5px]">
                                <thead class="sticky top-0 z-10 bg-slate-50 dark:bg-zinc-800 border-b border-slate-200 dark:border-zinc-700">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-semibold text-slate-600 dark:text-zinc-300 w-12">#</th>
                                        <th class="px-3 py-2 text-left font-semibold text-slate-600 dark:text-zinc-300">NIDN</th>
                                        <th class="px-3 py-2 text-left font-semibold text-slate-600 dark:text-zinc-300">Name</th>
                                        <th class="px-3 py-2 text-left font-semibold text-slate-600 dark:text-zinc-300">Email</th>
                                        <th class="px-3 py-2 text-left font-semibold text-slate-600 dark:text-zinc-300">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800">
                                    @foreach ($rows as $row)
                                        <tr class="{{ $row['valid'] ? '' : 'bg-rose-50/50 dark:bg-rose-900/10' }}">
                                            <td class="px-3 py-2 text-slate-500 dark:text-zinc-400 tabular-nums">{{ $row['row'] }}</td>
                                            <td class="px-3 py-2 text-slate-700 dark:text-zinc-300 font-mono">{{ $row['data']['nidn'] ?: '—' }}</td>
                                            <td class="px-3 py-2 text-slate-700 dark:text-zinc-300 truncate max-w-40">{{ $row['data']['full_name'] ?: '—' }}</td>
                                            <td class="px-3 py-2 text-slate-700 dark:text-zinc-300 truncate max-w-55">{{ $row['data']['email'] ?: '—' }}</td>
                                            <td class="px-3 py-2">
                                                @if ($row['valid'])
                                                    @if ($row['action'] === 'update')
                                                        <span class="inline-flex items-center gap-1 text-sky-600 dark:text-sky-400 font-medium">
                                                            <flux:icon.arrow-path class="size-3" />
                                                            {{ __("Update") }}
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-medium">
                                                            <flux:icon.plus-circle class="size-3" />
                                                            {{ __("New") }}
                                                        </span>
                                                    @endif
                                                @else
                                                    <div class="space-y-0.5">
                                                        @foreach ($row['errors'] as $err)
                                                            <div class="flex items-start gap-1 text-rose-600 dark:text-rose-400">
                                                                <flux:icon.exclamation-circle class="size-3 shrink-0 mt-0.5" />
                                                                <span>{{ $err }}</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <button type="button" wire:click="resetUpload"
                        class="inline-flex items-center gap-1.5 text-[11px] font-medium
                               text-slate-600 dark:text-zinc-400 hover:text-slate-800 dark:hover:text-zinc-200
                               underline underline-offset-2 transition-colors">
                        <flux:icon.arrow-path class="size-3" />
                        {{ __("Upload another file") }}
                    </button>
                </section>
            @endif

            {{-- Result --}}
            @if ($imported)
                <section class="space-y-3">
                    <header class="flex items-center gap-2">
                        <flux:icon.check-badge class="size-3.5 text-emerald-600 dark:text-emerald-400" />
                        <h4 class="text-[11px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400">
                            {{ __("Import Result") }}
                        </h4>
                        <div class="flex-1 h-px bg-slate-200 dark:bg-zinc-700"></div>
                    </header>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="p-4 rounded-2xl text-center
                                    bg-emerald-50 dark:bg-emerald-900/20
                                    border border-emerald-200 dark:border-emerald-800/60">
                            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 tabular-nums">
                                {{ $result['created'] }}
                            </div>
                            <div class="text-[10.5px] font-semibold text-emerald-700 dark:text-emerald-400 uppercase tracking-wide mt-0.5">
                                {{ __("Created") }}
                            </div>
                        </div>
                        <div class="p-4 rounded-2xl text-center
                                    bg-sky-50 dark:bg-sky-900/20
                                    border border-sky-200 dark:border-sky-800/60">
                            <div class="text-2xl font-bold text-sky-600 dark:text-sky-400 tabular-nums">
                                {{ $result['updated'] }}
                            </div>
                            <div class="text-[10.5px] font-semibold text-sky-700 dark:text-sky-400 uppercase tracking-wide mt-0.5">
                                {{ __("Updated") }}
                            </div>
                        </div>
                        <div class="p-4 rounded-2xl text-center
                                    bg-amber-50 dark:bg-amber-900/20
                                    border border-amber-200 dark:border-amber-800/60">
                            <div class="text-2xl font-bold text-amber-600 dark:text-amber-400 tabular-nums">
                                {{ $result['skipped'] }}
                            </div>
                            <div class="text-[10.5px] font-semibold text-amber-700 dark:text-amber-400 uppercase tracking-wide mt-0.5">
                                {{ __("Skipped") }}
                            </div>
                        </div>
                        <div class="p-4 rounded-2xl text-center
                                    bg-rose-50 dark:bg-rose-900/20
                                    border border-rose-200 dark:border-rose-800/60">
                            <div class="text-2xl font-bold text-rose-600 dark:text-rose-400 tabular-nums">
                                {{ count($result['failed']) }}
                            </div>
                            <div class="text-[10.5px] font-semibold text-rose-700 dark:text-rose-400 uppercase tracking-wide mt-0.5">
                                {{ __("Failed") }}
                            </div>
                        </div>
                    </div>

                    @if (! empty($result['failed']))
                        <div class="rounded-2xl border border-rose-200 dark:border-rose-800/60
                                    bg-rose-50/50 dark:bg-rose-900/10 p-3 space-y-1">
                            <p class="text-[10.5px] font-semibold uppercase tracking-wider text-rose-700 dark:text-rose-400">
                                {{ __("Failure details") }}
                            </p>
                            @foreach ($result['failed'] as $fail)
                                <div class="flex items-start gap-1.5 text-[11px] text-rose-700 dark:text-rose-300">
                                    <span class="font-mono shrink-0">Row {{ $fail['row'] }}:</span>
                                    <span class="truncate">{{ $fail['reason'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endif
        </div>

        {{-- FOOTER --}}
        <div class="shrink-0 flex flex-col-reverse sm:flex-row sm:justify-end gap-2
                    px-5 py-3.5
                    border-t border-slate-200 dark:border-zinc-700
                    bg-slate-50/50 dark:bg-zinc-900/50">

            <button type="button" wire:click="close"
                class="w-full sm:w-auto px-4 py-2 text-[12px] font-medium rounded-full
                       text-slate-700 dark:text-zinc-300
                       bg-white dark:bg-zinc-800
                       border border-slate-300 dark:border-zinc-600
                       hover:bg-slate-50 dark:hover:bg-zinc-700
                       shadow-sm shadow-zinc-200/40
                       hover:scale-[1.02] active:scale-[0.97]
                       transition-all duration-150">
                {{ $imported ? 'Close' : 'Cancel' }}
            </button>

            @if (! empty($rows) && ! $imported)
                @php
                    $validCount = count(array_filter($rows, fn($r) => $r['valid']));
                    $errorCount = count($rows) - $validCount;
                @endphp
                <button type="button" wire:click="import" wire:loading.attr="disabled" wire:target="import"
                    @disabled($errorCount > 0 || $validCount === 0)
                    title="{{ $errorCount > 0 ? 'Fix all errors before importing' : '' }}"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5
                           px-4 py-2 text-[12px] font-medium rounded-full
                           text-white
                           bg-emerald-600/90 hover:bg-emerald-600
                           shadow-sm shadow-emerald-500/20 hover:shadow-sm hover:shadow-emerald-500/30
                           disabled:opacity-60 disabled:cursor-not-allowed
                           hover:scale-[1.02] active:scale-[0.97]
                           transition-all duration-150">
                    <span wire:loading.remove wire:target="import">
                        Import
                    </span>
                    <span wire:loading wire:target="import">Importing…</span>
                </button>
            @endif
        </div>
    </div>
</div>
