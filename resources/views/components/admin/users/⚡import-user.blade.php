<?php

use App\Models\Role;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public $file;
    public array $rows = [];
    public array $result = [];
    public bool $show = false;
    public bool $imported = false;

    #[On('open-import-user')]
    public function open(): void
    {
        $this->reset(['file', 'rows', 'result', 'imported']);
        $this->resetErrorBag();
        $this->resetValidation();
        $this->show = true;
    }

    public function close(): void
    {
        $this->reset(['file', 'rows', 'result', 'imported']);
        $this->resetValidation();
        $this->show = false;
    }

    public function downloadTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['nidn', 'full_name', 'email', 'password', 'birthday', 'gender', 'phone_number', 'address'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray(['1234567890', 'Ahmad Fauzi', 'ahmad@ua.ac.id', '', '1990-05-15', 'laki-laki', '081234567890', 'Jl. Merdeka No. 1, Jakarta'], null, 'A2');
        $sheet->fromArray(['0987654321', 'Siti Nurhaliza', 'siti@ua.ac.id', 'secret123', '1988-11-20', 'perempuan', '', 'Jl. Sudirman 45, Bandung'], null, 'A3');

        $headerRange = 'A1:H1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle($headerRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('10B981');
        $sheet->getStyle($headerRange)->getFont()->getColor()->setRGB('FFFFFF');

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return response()->streamDownload(
            function () use ($writer) {
                $writer->save('php://output');
            },
            'template-User.xlsx',
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
        );
    }

    public function updatedFile(): void
    {
        $this->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:4096',
        ]);

        $this->parseAndValidate();
    }

    protected function parseAndValidate(): void
    {
        $this->rows = [];
        $this->imported = false;
        $this->result = [];

        try {
            $ext = strtolower($this->file->getClientOriginalExtension());

            $reader = match ($ext) {
                'xlsx' => new \PhpOffice\PhpSpreadsheet\Reader\Xlsx(),
                'xls'  => new \PhpOffice\PhpSpreadsheet\Reader\Xls(),
                default => null,
            };

            if (! $reader) {
                $this->addError('file', 'Unsupported file type. Only .xlsx and .xls are allowed.');
                return;
            }

            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($this->file->getRealPath());
            $data = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        } catch (\Throwable $e) {
            report($e);
            $this->addError('file', 'Could not read file: ' . $e->getMessage());
            return;
        }

        if (empty($data)) {
            $this->addError('file', 'File is empty.');
            return;
        }

        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $data[0]);
        $required = ['nidn', 'full_name', 'email', 'birthday', 'gender', 'address'];
        $missing = array_diff($required, $header);

        if (! empty($missing)) {
            $this->addError('file', 'Missing required columns: ' . implode(', ', $missing));
            return;
        }

        $seenNidn = $seenEmail = [];
        $rowNum = 1;

        for ($i = 1; $i < count($data); $i++) {
            $rowNum++;
            $dataRow = $data[$i] ?? [];

            if (count(array_filter($dataRow, fn ($v) => $v !== null && trim((string) $v) !== '')) === 0) {
                continue;
            }

            $row = [];
            foreach ($header as $j => $col) {
                $row[$col] = trim((string) ($dataRow[$j] ?? ''));
            }

            $errors = [];
            $existingUser = null;

            // NIDN
            if ($row['nidn'] === '') {
                $errors[] = 'NIDN required';
            } elseif (! preg_match('/^\d{8,12}$/', $row['nidn'])) {
                $errors[] = 'NIDN must be 8–12 digits';
            } elseif (isset($seenNidn[$row['nidn']])) {
                $errors[] = 'Duplicate NIDN in file';
            } else {
                $existingUser = User::where('nidn', $row['nidn'])->first();
            }

            // Email
            if ($row['email'] === '') {
                $errors[] = 'Email required';
            } elseif (! filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email format';
            } elseif (isset($seenEmail[strtolower($row['email'])])) {
                $errors[] = 'Duplicate email in file';
            } else {
                $emailOwner = User::where('email', $row['email'])->first();
                // Email boleh dipakai kalau milik user yang sama (update by NIDN),
                // tapi error kalau milik user lain.
                if ($emailOwner && (! $existingUser || $emailOwner->id !== $existingUser->id)) {
                    $errors[] = 'Email already used by another user';
                }
            }

            if ($row['full_name'] === '') {
                $errors[] = 'Full name required';
            }

            if ($row['birthday'] === '') {
                $errors[] = 'Birthday required';
            } elseif (! strtotime($row['birthday'])) {
                $errors[] = 'Invalid birthday format';
            }

            $gender = strtolower($row['gender']);
            if ($gender === '') {
                $errors[] = 'Gender required';
            } elseif (! in_array($gender, ['laki-laki', 'perempuan'], true)) {
                $errors[] = 'Gender must be "laki-laki" or "perempuan"';
            }

            if ($row['address'] === '') {
                $errors[] = 'Address required';
            }

            if (! empty($row['password']) && strlen($row['password']) < 8) {
                $errors[] = 'Password min. 8 characters';
            }

            if ($row['nidn'] !== '') {
                $seenNidn[$row['nidn']] = true;
            }
            if ($row['email'] !== '') {
                $seenEmail[strtolower($row['email'])] = true;
            }

            $this->rows[] = [
                'row'         => $rowNum,
                'data'        => $row,
                'errors'      => $errors,
                'valid'       => empty($errors),
                'existing_id' => $existingUser?->id,
                'action'      => $existingUser ? 'update' : 'create',
            ];
        }

        if (empty($this->rows)) {
            $this->addError('file', 'No data rows found.');
        }
    }

    public function import(): void
    {
        $roleId = Role::where('role_code', 'USER')->value('id');
        if (! $roleId) {
            Flux::toast('Role USER not found.', variant: 'danger');
            return;
        }

        $validRows = array_filter($this->rows, fn ($r) => $r['valid']);

        $created = 0;
        $updated = 0;
        $failed = [];

        foreach ($validRows as $row) {
            try {
                $data = $row['data'];

                $attributes = [
                    'role_id'           => $roleId,
                    'full_name'         => $data['full_name'],
                    'email'             => $data['email'],
                    'birthday'          => date('Y-m-d', strtotime($data['birthday'])),
                    'email_verified_at' => now(),
                    'gender'            => strtolower($data['gender']),
                    'phone_number'      => $data['phone_number'] ?? null,
                    'address'           => $data['address'],
                ];

                // Update password hanya kalau kolom password diisi
                if (! empty($data['password'])) {
                    $attributes['password'] = Hash::make($data['password']);
                    $attributes['raw_password'] = $data['password'];
                }

                if ($row['existing_id']) {
                    // Update — jangan sentuh password kalau kosong
                    User::where('id', $row['existing_id'])->update($attributes);
                    $updated++;
                } else {
                    // Create — password fallback ke NIDN kalau kosong
                    if (empty($data['password'])) {
                        $attributes['password'] = Hash::make($data['nidn']);
                        $attributes['raw_password'] = $data['nidn'];
                    }
                    $attributes['nidn'] = $data['nidn'];
                    User::create($attributes);
                    $created++;
                }
            } catch (\Throwable $e) {
                report($e);
                $failed[] = ['row' => $row['row'], 'reason' => $e->getMessage()];
            }
        }

        $this->result = [
            'created' => $created,
            'updated' => $updated,
            'skipped' => count($this->rows) - count($validRows),
            'failed'  => $failed,
        ];
        $this->imported = true;

        if ($created > 0 || $updated > 0) {
            $parts = [];
            if ($created > 0) $parts[] = "{$created} created";
            if ($updated > 0) $parts[] = "{$updated} updated";
            Flux::toast('Import done: ' . implode(', ', $parts) . '.', variant: 'success');
            $this->dispatch('User-imported');
        }
    }

    public function resetUpload(): void
    {
        $this->reset(['file', 'rows', 'result', 'imported']);
        $this->resetValidation();
    }
};
?>

<div x-data x-show="$wire.show" x-transition.opacity x-cloak x-on:keydown.escape.window="$wire.close()"
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4
           font-sans"
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
                            Import Users
                        </h3>
                        <p class="text-[12px] text-white/80 mt-0.5 leading-tight">
                            Upload an Excel file (.xlsx / .xls) to bulk-add or update Users
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

            @if (empty($rows) && !$imported)
                {{-- Section: Before you start --}}
                <section class="space-y-3">
                    <header class="flex items-center gap-2">
                        <flux:icon.document-arrow-up class="size-3.5 text-emerald-600 dark:text-emerald-400" />
                        <h4
                            class="text-[11px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400">
                            Before you start
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
                            <div
                                class="w-7 h-7 rounded-full bg-emerald-600/15 dark:bg-emerald-500/20
                                        flex items-center justify-center shrink-0">
                                <flux:icon.arrow-down-tray class="size-3.5 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <span class="flex-1 text-[12px] font-semibold
                                        text-emerald-800 dark:text-emerald-300">
                                Download Template Excel
                            </span>
                            <flux:icon.chevron-down
                                class="size-3.5 text-emerald-600 dark:text-emerald-400 transition-transform"
                                x-bind:class="open ? 'rotate-180' : ''" />
                        </button>

                        <div x-show="open" x-collapse class="px-4 pb-4 pt-0.5 space-y-3">
                            <p class="text-[11.5px] leading-relaxed
                                    text-emerald-700/90 dark:text-emerald-400/90">
                                Download this Excel template so the column names and order match what the
                                system expects. You can also prepare your own file as long as the
                                required columns are present.
                            </p>

                            <button type="button" wire:click="downloadTemplate"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full
                                    text-[11.5px] font-medium text-white
                                    bg-emerald-600 hover:bg-emerald-700
                                    shadow-sm shadow-emerald-500/25
                                    hover:scale-[1.02] active:scale-[0.97]
                                    transition-all duration-150">
                                <flux:icon.arrow-down-tray class="size-3.5" />
                                Download
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
                            <div
                                class="w-7 h-7 rounded-full bg-sky-600/15 dark:bg-sky-500/20
                                        flex items-center justify-center shrink-0">
                                <flux:icon.information-circle class="size-3.5 text-sky-600 dark:text-sky-400" />
                            </div>
                            <span
                                class="flex-1 text-[12px] font-semibold
                                         text-sky-800 dark:text-sky-300">
                                Column Guide
                            </span>
                            <span
                                class="shrink-0 px-2 py-0.5 rounded-full
                                         bg-sky-600/15 dark:bg-sky-500/20
                                         text-[10px] font-semibold
                                         text-sky-700 dark:text-sky-400">
                                8 columns
                            </span>
                            <flux:icon.chevron-down class="size-3.5 text-sky-600 dark:text-sky-400 transition-transform"
                                x-bind:class="open ? 'rotate-180' : ''" />
                        </button>

                        <div x-show="open" x-collapse class="px-4 pb-4 pt-0.5">
                            <table class="w-full text-[11.5px]">
                                <thead>
                                    <tr class="text-left text-sky-700/80 dark:text-sky-400/80">
                                        <th class="pb-1.5 font-semibold">Column</th>
                                        <th class="pb-1.5 font-semibold w-20 text-center">Required</th>
                                        <th class="pb-1.5 font-semibold">Notes</th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-sky-200/60 dark:divide-sky-800/40
                                              text-sky-900/90 dark:text-sky-200/90">
                                    <tr>
                                        <td class="py-1.5 font-mono font-semibold">nidn</td>
                                        <td class="py-1.5 text-center">
                                            <span
                                                class="inline-block px-2 py-0.5 rounded-full
                                                         bg-rose-100 dark:bg-rose-900/40
                                                         text-rose-700 dark:text-rose-400
                                                         text-[10px] font-semibold">Required</span>
                                        </td>
                                        <td class="py-1.5">8–12 digits, must be unique</td>
                                    </tr>
                                    <tr>
                                        <td class="py-1.5 font-mono font-semibold">full_name</td>
                                        <td class="py-1.5 text-center">
                                            <span
                                                class="inline-block px-2 py-0.5 rounded-full
                                                         bg-rose-100 dark:bg-rose-900/40
                                                         text-rose-700 dark:text-rose-400
                                                         text-[10px] font-semibold">Required</span>
                                        </td>
                                        <td class="py-1.5">User's full name</td>
                                    </tr>
                                    <tr>
                                        <td class="py-1.5 font-mono font-semibold">email</td>
                                        <td class="py-1.5 text-center">
                                            <span
                                                class="inline-block px-2 py-0.5 rounded-full
                                                         bg-rose-100 dark:bg-rose-900/40
                                                         text-rose-700 dark:text-rose-400
                                                         text-[10px] font-semibold">Required</span>
                                        </td>
                                        <td class="py-1.5">Must be a valid email and unique</td>
                                    </tr>
                                    <tr>
                                        <td class="py-1.5 font-mono font-semibold">birthday</td>
                                        <td class="py-1.5 text-center">
                                            <span
                                                class="inline-block px-2 py-0.5 rounded-full
                                                         bg-rose-100 dark:bg-rose-900/40
                                                         text-rose-700 dark:text-rose-400
                                                         text-[10px] font-semibold">Required</span>
                                        </td>
                                        <td class="py-1.5">
                                            Format <code class="font-mono">YYYY-MM-DD</code>, e.g.
                                            <code class="font-mono">1990-05-15</code>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="py-1.5 font-mono font-semibold">gender</td>
                                        <td class="py-1.5 text-center">
                                            <span
                                                class="inline-block px-2 py-0.5 rounded-full
                                                         bg-rose-100 dark:bg-rose-900/40
                                                         text-rose-700 dark:text-rose-400
                                                         text-[10px] font-semibold">Required</span>
                                        </td>
                                        <td class="py-1.5">
                                            <code class="font-mono">laki-laki</code> or
                                            <code class="font-mono">perempuan</code>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="py-1.5 font-mono font-semibold">address</td>
                                        <td class="py-1.5 text-center">
                                            <span
                                                class="inline-block px-2 py-0.5 rounded-full
                                                         bg-rose-100 dark:bg-rose-900/40
                                                         text-rose-700 dark:text-rose-400
                                                         text-[10px] font-semibold">Required</span>
                                        </td>
                                        <td class="py-1.5">Full address</td>
                                    </tr>
                                    <tr>
                                        <td class="py-1.5 font-mono font-semibold">password</td>
                                        <td class="py-1.5 text-center">
                                            <span
                                                class="inline-block px-2 py-0.5 rounded-full
                                                         bg-slate-100 dark:bg-zinc-800
                                                         text-slate-600 dark:text-zinc-400
                                                         text-[10px] font-semibold">Optional</span>
                                        </td>
                                        <td class="py-1.5">
                                            Min. 8 characters. Leave empty to use NIDN as the default password
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="py-1.5 font-mono font-semibold">phone_number</td>
                                        <td class="py-1.5 text-center">
                                            <span
                                                class="inline-block px-2 py-0.5 rounded-full
                                                         bg-slate-100 dark:bg-zinc-800
                                                         text-slate-600 dark:text-zinc-400
                                                         text-[10px] font-semibold">Optional</span>
                                        </td>
                                        <td class="py-1.5">
                                            Phone number, e.g. <code class="font-mono">081234567890</code>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <div
                                class="mt-3 p-3 rounded-xl
                                        bg-sky-100/60 dark:bg-sky-900/25
                                        border border-sky-200 dark:border-sky-800/60">
                                <p
                                    class="text-[11px] leading-relaxed
                                          text-sky-800 dark:text-sky-300">
                                    <strong>Tip:</strong> Column order doesn't have to match the
                                    template — what matters is the column names. Extra columns that
                                    aren't recognized will simply be ignored.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Section: Upload --}}
                <section class="space-y-3">
                    <header class="flex items-center gap-2">
                        <flux:icon.arrow-up-tray class="size-3.5 text-emerald-600 dark:text-emerald-400" />
                        <h4
                            class="text-[11px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400">
                            Upload File
                        </h4>
                        <div class="flex-1 h-px bg-slate-200 dark:bg-zinc-700"></div>
                    </header>

                    <div>
                        <label class="block cursor-pointer">
                            <input type="file" wire:model="file" accept=".xlsx,.xls" class="sr-only" />
                            <div
                                class="flex flex-col items-center gap-2 p-6 rounded-2xl
                                        border-2 border-dashed
                                        border-slate-300 dark:border-zinc-600
                                        hover:border-emerald-500 dark:hover:border-emerald-500
                                        bg-slate-50 dark:bg-zinc-800/40
                                        hover:bg-emerald-50/50 dark:hover:bg-emerald-900/10
                                        transition-colors">

                                <div wire:loading.remove wire:target="file">
                                    <div
                                        class="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-900/40
                                                flex items-center justify-center mx-auto">
                                        <flux:icon.arrow-up-tray
                                            class="size-5 text-emerald-600 dark:text-emerald-400" />
                                    </div>
                                    <p
                                        class="mt-3 text-[13px] font-medium text-slate-700 dark:text-zinc-300 text-center">
                                        Click to select an Excel file
                                    </p>
                                    <p class="mt-1 text-[11px] text-slate-500 dark:text-zinc-400 text-center">
                                        Max. 4 MB · .xlsx, .xls
                                    </p>
                                </div>

                                <div wire:loading wire:target="file" class="text-center">
                                    <svg class="animate-spin size-6 mx-auto text-emerald-600" viewBox="0 0 24 24"
                                        fill="none">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4" opacity=".25" />
                                        <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4"
                                            stroke-linecap="round" />
                                    </svg>
                                    <p class="mt-3 text-[12px] font-medium text-emerald-600">Reading file…</p>
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
            @if (!empty($rows) && !$imported)
                @php
                    $validCount = count(array_filter($rows, fn($r) => $r['valid']));
                    $errorCount = count($rows) - $validCount;
                @endphp

                <section class="space-y-3">
                    <header class="flex items-center gap-2">
                        <flux:icon.table-cells class="size-3.5 text-emerald-600 dark:text-emerald-400" />
                        <h4
                            class="text-[11px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400">
                            Preview
                        </h4>
                        <div class="flex-1 h-px bg-slate-200 dark:bg-zinc-700"></div>
                    </header>

                    <div class="flex flex-wrap gap-2">
                        <span
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                                     bg-slate-100 dark:bg-zinc-800
                                     text-[11px] font-medium text-slate-700 dark:text-zinc-300">
                            <flux:icon.queue-list class="size-3" />
                            Total: {{ count($rows) }}
                        </span>
                        @if ($validCount > 0)
                            <span
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                                         bg-emerald-100 dark:bg-emerald-900/30
                                         text-[11px] font-medium text-emerald-700 dark:text-emerald-400">
                                <flux:icon.check-circle class="size-3" />
                                Valid: {{ $validCount }}
                            </span>
                        @endif
                        @if ($errorCount > 0)
                            <span
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
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
                                <thead
                                    class="sticky top-0 z-10 bg-slate-50 dark:bg-zinc-800 border-b border-slate-200 dark:border-zinc-700">
                                    <tr>
                                        <th
                                            class="px-3 py-2 text-left font-semibold text-slate-600 dark:text-zinc-300 w-12">
                                            #</th>
                                        <th
                                            class="px-3 py-2 text-left font-semibold text-slate-600 dark:text-zinc-300">
                                            NIDN</th>
                                        <th
                                            class="px-3 py-2 text-left font-semibold text-slate-600 dark:text-zinc-300">
                                            Name</th>
                                        <th
                                            class="px-3 py-2 text-left font-semibold text-slate-600 dark:text-zinc-300">
                                            Email</th>
                                        <th
                                            class="px-3 py-2 text-left font-semibold text-slate-600 dark:text-zinc-300">
                                            Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800">
                                    @foreach ($rows as $row)
                                        <tr class="{{ $row['valid'] ? '' : 'bg-rose-50/50 dark:bg-rose-900/10' }}">
                                            <td class="px-3 py-2 text-slate-500 dark:text-zinc-400 tabular-nums">
                                                {{ $row['row'] }}</td>
                                            <td class="px-3 py-2 text-slate-700 dark:text-zinc-300 font-mono">
                                                {{ $row['data']['nidn'] ?: '—' }}</td>
                                            <td
                                                class="px-3 py-2 text-slate-700 dark:text-zinc-300 truncate max-w-[160px]">
                                                {{ $row['data']['full_name'] ?: '—' }}</td>
                                            <td
                                                class="px-3 py-2 text-slate-700 dark:text-zinc-300 truncate max-w-[220px]">
                                                {{ $row['data']['email'] ?: '—' }}</td>
                                            <td class="px-3 py-2">
                                                @if ($row['valid'])
                                                    <span
                                                        class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-medium">
                                                        <flux:icon.check-circle class="size-3" />
                                                        OK
                                                    </span>
                                                @else
                                                    <div class="space-y-0.5">
                                                        @foreach ($row['errors'] as $err)
                                                            <div
                                                                class="flex items-start gap-1 text-rose-600 dark:text-rose-400">
                                                                <flux:icon.exclamation-circle
                                                                    class="size-3 shrink-0 mt-0.5" />
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
                        Upload another file
                    </button>
                </section>
            @endif

            {{-- Result --}}
            @if ($imported)
                <section class="space-y-3">
                    <header class="flex items-center gap-2">
                        <flux:icon.check-badge class="size-3.5 text-emerald-600 dark:text-emerald-400" />
                        <h4
                            class="text-[11px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400">
                            Import Result
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
                                Created
                            </div>
                        </div>
                        <div class="p-4 rounded-2xl text-center
                                    bg-sky-50 dark:bg-sky-900/20
                                    border border-sky-200 dark:border-sky-800/60">
                            <div class="text-2xl font-bold text-sky-600 dark:text-sky-400 tabular-nums">
                                {{ $result['updated'] }}
                            </div>
                            <div class="text-[10.5px] font-semibold text-sky-700 dark:text-sky-400 uppercase tracking-wide mt-0.5">
                                Updated
                            </div>
                        </div>
                        <div class="p-4 rounded-2xl text-center
                                    bg-amber-50 dark:bg-amber-900/20
                                    border border-amber-200 dark:border-amber-800/60">
                            <div class="text-2xl font-bold text-amber-600 dark:text-amber-400 tabular-nums">
                                {{ $result['skipped'] }}
                            </div>
                            <div class="text-[10.5px] font-semibold text-amber-700 dark:text-amber-400 uppercase tracking-wide mt-0.5">
                                Skipped
                            </div>
                        </div>
                        <div class="p-4 rounded-2xl text-center
                                    bg-rose-50 dark:bg-rose-900/20
                                    border border-rose-200 dark:border-rose-800/60">
                            <div class="text-2xl font-bold text-rose-600 dark:text-rose-400 tabular-nums">
                                {{ count($result['failed']) }}
                            </div>
                            <div class="text-[10.5px] font-semibold text-rose-700 dark:text-rose-400 uppercase tracking-wide mt-0.5">
                                Failed
                            </div>
                        </div>
                    </div>

                    @if (!empty($result['failed']))
                        <div
                            class="rounded-2xl border border-rose-200 dark:border-rose-800/60
                                    bg-rose-50/50 dark:bg-rose-900/10 p-3 space-y-1">
                            <p
                                class="text-[10.5px] font-semibold uppercase tracking-wider text-rose-700 dark:text-rose-400">
                                Failure details
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
        <div
            class="shrink-0 flex flex-col-reverse sm:flex-row sm:justify-end gap-2
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

            @if (!empty($rows) && !$imported)
                @php $validCount = count(array_filter($rows, fn($r) => $r['valid'])); @endphp
                <button type="button" wire:click="import" wire:loading.attr="disabled" wire:target="import"
                    @disabled($validCount === 0)
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5
                           px-4 py-2 text-[12px] font-medium rounded-full
                           text-white
                           bg-emerald-600/90 hover:bg-emerald-600
                           shadow-sm shadow-emerald-500/20 hover:shadow-sm hover:shadow-emerald-500/30
                           disabled:opacity-60 disabled:cursor-not-allowed
                           hover:scale-[1.02] active:scale-[0.97]
                           transition-all duration-150">
                    <svg wire:loading wire:target="import" class="animate-spin size-3.5" viewBox="0 0 24 24"
                        fill="none">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                            opacity=".25" />
                        <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
                    </svg>
                    <flux:icon.arrow-down-on-square wire:loading.remove wire:target="import" class="size-3.5" />
                    <span wire:loading.remove wire:target="import">Import {{ $validCount }} row(s)</span>
                    <span wire:loading wire:target="import">Importing…</span>
                </button>
            @endif
        </div>
    </div>
</div>
