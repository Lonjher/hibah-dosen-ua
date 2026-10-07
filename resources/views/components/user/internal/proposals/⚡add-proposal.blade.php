<?php

use App\Livewire\Forms\BudgetProposalForm;
use App\Livewire\Forms\ProposalForm;
use App\Livewire\Forms\ProposalMemberForm;
use App\Livewire\Forms\ProposalStudentForm;
use App\Models\BudgetProposal;
use App\Models\Period;
use App\Models\Proposal;
use App\Models\ProposalMember;
use App\Models\ProposalStudent;
use App\Models\ResearchScheme;
use App\Models\User;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public ProposalForm $form;
    public BudgetProposalForm $budgetForm;
    public ProposalMemberForm $memberForm;
    public ProposalStudentForm $studentForm;

    public bool $isResearch = true;
    public int $step = 1;

    // ── Member picker helpers ──
    public string $memberSearch = '';
    public bool $memberHasSearched = false;

    // ── Student form toggle ──
    public bool $showStudentForm = false;

    /**
     * Buffer yang HANYA di-commit ke DB saat finish().
     * @var array<int, array{item_name:string, amount:int}>
     */
    public array $pendingBudgetItems = [];

    /** @var array<int, array{user_id:int, name:string, nidn:?string, role:string}> */
    public array $pendingMembers = [];

    /** @var array<int, array{nim:string, name:string, program_study:string, role:string}> */
    public array $pendingStudents = [];

    protected function getActivePeriod(): ?Period
    {
        return Period::query()
            ->where('is_active', true)
            ->whereDate('open_from', '<=', now())
            ->whereDate('open_to', '>=', now())
            ->first();
    }

    public function mount(bool $isResearch = true): void
    {
        $this->isResearch = $isResearch;
        $this->form->is_research = $isResearch;
        $this->form->status = 'pending';

        $activePeriod = $this->getActivePeriod();
        if ($activePeriod) {
            $this->form->period_id = $activePeriod->id;
        }
    }

    /* ============================================================
     |  STEP NAVIGATION
     ============================================================ */

    public function nextStep(): void
    {
        if ($this->step !== 1) {
            return;
        }

        // Guard: pastikan periode aktif tersedia
        $activePeriod = $this->getActivePeriod();

        if (! $activePeriod) {
            Flux::toast('Tidak ada periode aktif. Hubungi admin.', variant: 'danger');
            return;
        }

        $this->form->period_id = $activePeriod->id;

        // Validasi step 1 (metadata + file proposal)
        $this->form->validateStep1();

        // ❌ JANGAN simpan ke DB dulu.
        // File disimpan di temporary upload state Livewire sampai finish().

        $this->step = 2;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function prevStep(): void
    {
        if ($this->step > 1) {
            $this->step = 1;
            $this->resetErrorBag();
            $this->resetValidation();
        }
    }

    /* ============================================================
     |  BUDGET ITEMS (Step 2, buffered)
     ============================================================ */

    public function addBudgetItem(): void
    {
        // Validasi input budget form
        try {
            $this->budgetForm->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        }

        $amount = (int) preg_replace('/\D/', '', (string) $this->budgetForm->amount);

        // Hitung total dari buffer + limit skema
        $budgetLimit = (int) (ResearchScheme::find($this->form->research_scheme_id)?->budget_limit ?? 0);
        $currentTotal = collect($this->pendingBudgetItems)->sum('amount');

        if ($currentTotal + $amount > $budgetLimit) {
            $remaining = max($budgetLimit - $currentTotal, 0);
            $this->budgetForm->addError(
                'amount',
                'Melebihi batas anggaran. Maksimal: Rp ' . number_format($remaining, 0, ',', '.')
            );
            return;
        }

        $this->pendingBudgetItems[] = [
            'item_name' => $this->budgetForm->item_name,
            'amount'    => $amount,
        ];

        $this->budgetForm->reset('item_name', 'amount');
        $this->resetErrorBag();
        $this->resetValidation();

        Flux::toast('Item anggaran ditambahkan.', variant: 'success');
    }

    public function removeBudgetItem(int $index): void
    {
        if (isset($this->pendingBudgetItems[$index])) {
            unset($this->pendingBudgetItems[$index]);
            $this->pendingBudgetItems = array_values($this->pendingBudgetItems);
            Flux::toast('Item anggaran dihapus.', variant: 'success');
        }
    }

    /* ============================================================
     |  PROPOSAL MEMBER (Step 1, buffered)
     ============================================================ */

    public function updatedMemberSearch(): void
    {
        $this->memberHasSearched = true;
    }

    public function selectMember(int $userId): void
    {
        $user = User::find($userId);
        if (! $user) {
            return;
        }

        // Cek bukan author
        if ($userId === (int) auth()->id()) {
            Flux::toast('Anda otomatis tercatat sebagai Ketua.', variant: 'danger');
            $this->memberSearch = '';
            $this->memberHasSearched = false;
            return;
        }

        // Cek duplicate
        foreach ($this->pendingMembers as $m) {
            if ((int) $m['user_id'] === $userId) {
                Flux::toast('User ini sudah ditambahkan.', variant: 'danger');
                $this->memberSearch = '';
                $this->memberHasSearched = false;
                return;
            }
        }

        $this->pendingMembers[] = [
            'user_id' => $user->id,
            'name'    => $user->full_name,
            'nidn'    => $user->nidn,
            'role'    => 'member',
        ];

        $this->memberSearch = '';
        $this->memberHasSearched = false;
        Flux::toast('Anggota dosen ditambahkan.', variant: 'success');
    }

    public function removePendingMember(int $index): void
    {
        if (isset($this->pendingMembers[$index])) {
            unset($this->pendingMembers[$index]);
            $this->pendingMembers = array_values($this->pendingMembers);
            Flux::toast('Anggota dosen dihapus.', variant: 'success');
        }
    }

    /* ============================================================
     |  PROPOSAL STUDENT (Step 1, buffered)
     ============================================================ */

    public function addStudent(): void
    {
        $this->validate([
            'studentForm.nim'           => ['required', 'string', 'max:50'],
            'studentForm.name'          => ['required', 'string', 'max:255'],
            'studentForm.program_study' => ['required', 'string', 'max:255'],
            'studentForm.role'          => ['required', 'in:leader,member'],
        ], [
            'studentForm.nim.required'           => 'NIM wajib diisi.',
            'studentForm.name.required'          => 'Nama mahasiswa wajib diisi.',
            'studentForm.program_study.required' => 'Program studi wajib diisi.',
            'studentForm.role.in'                => 'Role harus leader atau member.',
        ]);

        // Cek duplicate NIM
        foreach ($this->pendingStudents as $s) {
            if ($s['nim'] === $this->studentForm->nim) {
                $this->addError('studentForm.nim', 'NIM ini sudah ditambahkan.');
                return;
            }
        }

        $this->pendingStudents[] = [
            'nim'           => $this->studentForm->nim,
            'name'          => $this->studentForm->name,
            'program_study' => $this->studentForm->program_study,
            'role'          => $this->studentForm->role,
        ];

        $this->studentForm->reset('nim', 'name', 'program_study', 'role');
        $this->studentForm->role = 'member';
        $this->showStudentForm = false;
        $this->resetErrorBag('studentForm.*');
        $this->resetValidation('studentForm.*');

        Flux::toast('Mahasiswa ditambahkan.', variant: 'success');
    }

    public function removePendingStudent(int $index): void
    {
        if (isset($this->pendingStudents[$index])) {
            unset($this->pendingStudents[$index]);
            $this->pendingStudents = array_values($this->pendingStudents);
            Flux::toast('Mahasiswa dihapus.', variant: 'success');
        }
    }

    public function toggleStudentForm(): void
    {
        $this->showStudentForm = ! $this->showStudentForm;

        if (! $this->showStudentForm) {
            $this->studentForm->reset('nim', 'name', 'program_study', 'role');
            $this->studentForm->role = 'member';
            $this->resetErrorBag('studentForm.*');
            $this->resetValidation('studentForm.*');
        }
    }

    /* ============================================================
     |  FINISH — commit semua ke DB
     ============================================================ */

    public function finish(): void
    {
        // ── Validasi Step 2: RAB wajib ──
        $this->form->validateStep2();

        // ── Validasi buffer budget minimal 1 item ──
        if (empty($this->pendingBudgetItems)) {
            Flux::toast('Tambahkan minimal 1 item anggaran.', variant: 'danger');
            return;
        }

        $budgetLimit = (int) (ResearchScheme::find($this->form->research_scheme_id)?->budget_limit ?? 0);
        $total = collect($this->pendingBudgetItems)->sum('amount');

        if ($total > $budgetLimit) {
            Flux::toast('Total anggaran melebihi batas.', variant: 'danger');
            return;
        }

        try {
            // ── 1. Store file utama (proposal) ──
            $filePath = $this->form->storeFile() ?? '';

            // ── 2. Store file RAB ──
            $rabPath = $this->form->storeRabFile() ?? '';

            // ── 3. Create Proposal ──
            $proposal = Proposal::create([
                'user_id'            => auth()->id(),
                'research_scheme_id' => $this->form->research_scheme_id,
                'title'              => $this->form->title,
                'summary'            => $this->form->summary,
                'keywords'           => $this->form->keywords,
                'period_id'          => $this->form->period_id,
                'is_research'        => $this->isResearch,
                'status'             => 'pending',
                'reviewer_id'        => null,
                'file_path'          => $filePath,
                'rab_path'           => $rabPath,
            ]);

            // ── 4. Persist budget items ──
            foreach ($this->pendingBudgetItems as $item) {
                BudgetProposal::create([
                    'proposal_id' => $proposal->id,
                    'item_name'   => $item['item_name'],
                    'amount'      => $item['amount'],
                ]);
            }

            // ── 5. Persist members (dosen) ──
            foreach ($this->pendingMembers as $m) {
                ProposalMember::create([
                    'proposal_id' => $proposal->id,
                    'user_id'     => $m['user_id'],
                    'role'        => $m['role'],
                ]);
            }

            // ── 6. Persist students ──
            foreach ($this->pendingStudents as $s) {
                ProposalStudent::create([
                    'proposal_id'   => $proposal->id,
                    'nim'           => $s['nim'],
                    'name'          => $s['name'],
                    'program_study' => $s['program_study'],
                    'role'          => $s['role'],
                ]);
            }

            Flux::toast('Proposal berhasil disimpan.', variant: 'success');
            $this->dispatch('proposal-added', message: 'Proposal berhasil ditambahkan.');
            $this->resetAll();

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            Flux::toast('Gagal menyimpan proposal. Silakan coba lagi.', variant: 'danger');
        }
    }

    public function resetAll(): void
    {
        $this->form->reset();
        $this->budgetForm->reset();
        $this->memberForm->reset();
        $this->studentForm->reset();

        $this->reset([
            'step',
            'memberSearch',
            'memberHasSearched',
            'showStudentForm',
            'pendingBudgetItems',
            'pendingMembers',
            'pendingStudents',
        ]);

        $this->step = 1;
        $this->form->is_research = $this->isResearch;
        $this->form->status = 'pending';

        // Re-set periode aktif (kalau ada)
        $activePeriod = $this->getActivePeriod();
        if ($activePeriod) {
            $this->form->period_id = $activePeriod->id;
        }

        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function with(): array
    {
        $budgetLimit = 0;
        if ($this->form->research_scheme_id) {
            $budgetLimit = (int) (ResearchScheme::find($this->form->research_scheme_id)?->budget_limit ?? 0);
        }

        $budgetTotal = (int) collect($this->pendingBudgetItems)->sum('amount');

        // ── Member suggestions ──
        $memberSuggestions = collect();
        $usedIds = collect($this->pendingMembers)->pluck('user_id')->all();
        $usedIds[] = auth()->id();

        if ($this->memberSearch !== '' || ! $this->memberHasSearched) {
            $memberSuggestions = User::query()
                ->whereHas('role', fn ($q) => $q->where('role_code', 'USER'))
                ->whereNotIn('id', $usedIds)
                ->when($this->memberSearch, fn ($q) => $q->where(fn ($q) => $q
                    ->where('full_name', 'like', "%{$this->memberSearch}%")
                    ->orWhere('nidn', 'like', "%{$this->memberSearch}%")))
                ->orderBy('full_name')
                ->limit(5)
                ->get();
        }

        $theme = $this->isResearch
            ? [
                'icon'     => 'document-plus',
                'gradient' => 'from-emerald-600 to-emerald-500',
                'label'    => 'Research',
                'accent'   => 'emerald',
            ]
            : [
                'icon'     => 'heart',
                'gradient' => 'from-rose-600 to-rose-500',
                'label'    => 'Community Service',
                'accent'   => 'rose',
            ];

        return [
            'schemes'            => ResearchScheme::where('is_active', true)->orderBy('name')->get(),
            'activePeriod'       => $this->getActivePeriod(),
            'budgetTotal'        => $budgetTotal,
            'budgetLimit'        => $budgetLimit,
            'budgetRemaining'    => $budgetLimit - $budgetTotal,
            'budgetPercent'      => $budgetLimit > 0 ? min(round(($budgetTotal / $budgetLimit) * 100, 1), 100) : 0,
            'memberSuggestions'  => $memberSuggestions,
            'theme'              => $theme,
        ];
    }
};
?>

<div x-data="{
    show: false,
    init() {
        window.addEventListener('open-add-proposal', () => { this.show = true; });
        window.addEventListener('proposal-added', () => { this.show = false; });
    }
}" x-show="show" x-transition.opacity x-cloak
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4"
    @click.self="show = false">

    <div class="flex max-h-[90vh] w-full sm:max-w-2xl flex-col overflow-hidden
                bg-white dark:bg-zinc-900 rounded-t-3xl sm:rounded-3xl shadow-2xl
                border border-slate-200 dark:border-zinc-700"
        @click.stop>

        <form class="flex min-h-0 flex-1 flex-col">

            {{-- HEADER --}}
            <div class="shrink-0 bg-gradient-to-r {{ $theme['gradient'] }}
                        px-5 sm:px-6 py-4 rounded-t-3xl sm:rounded-t-3xl">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                        <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                            <flux:icon :name="$theme['icon']" class="size-4 text-white" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-heading text-[15px] font-semibold text-white leading-tight">
                                {{ __("Create new ") }} {{ $theme['label'] }}
                            </h3>
                            <p class="text-[11px] text-white/75 mt-0.5">
                                {{ __("Step ") . $step . __("of 2 — ") }}
                                {{ $step === 1 ? 'Metadata & Anggota' : 'Anggaran & RAB' }}
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="show = false"
                        class="shrink-0 w-7 h-7 rounded-full flex items-center justify-center
                               text-white/80 hover:text-white hover:bg-white/10
                               hover:scale-110 active:scale-95 transition-all duration-150">
                        <flux:icon.x-mark class="size-4" />
                    </button>
                </div>
            </div>

            {{-- ERROR SUMMARY --}}
            @if ($errors->any())
                <div class="shrink-0 px-5 sm:px-6 py-2">
                    <div class="rounded-2xl border border-rose-200 dark:border-rose-800/60
                                bg-rose-50 dark:bg-rose-900/20 p-3">
                        <div class="flex items-start gap-2">
                            <flux:icon.exclamation-triangle
                                class="size-4 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5" />
                            <div class="flex-1 min-w-0">
                                <p class="text-[11.5px] font-semibold text-rose-800 dark:text-rose-300">
                                    {{ __("Please complete: ") }}
                                </p>
                                <ul class="mt-1 space-y-0.5 list-disc list-inside">
                                    @foreach ($errors->all() as $error)
                                        <li class="text-[10.5px] leading-relaxed text-rose-700 dark:text-rose-300">
                                            {{ $error }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- BODY --}}
            <div class="flex-1 overflow-y-auto px-5 sm:px-6 py-5 space-y-4">

                {{-- ═══════════════════════════════════════════════ --}}
                {{-- STEP 1: METADATA + ANGGOTA                       --}}
                {{-- ═══════════════════════════════════════════════ --}}
                @if ($step === 1)
                    <div class="space-y-4">

                        {{-- File Proposal --}}
                        <x-input
                            type="file"
                            wire:model="form.file"
                            label="Proposal File"
                            required
                            accept=".pdf,.doc,.docx"
                            hint="Max 10 MB · PDF, DOC, DOCX"
                            :class="$errors->has('form.file')
                                ? '[&_input]:!border-rose-400 dark:[&_input]:!border-rose-500 [&_input]:!ring-2 [&_input]:!ring-rose-500/25'
                                : ''" />

                        {{-- Judul --}}
                        <x-input
                            wire:model="form.title"
                            label="Title"
                            required
                            placeholder="Proposal title"
                            :class="$errors->has('form.title')
                                ? '[&_input]:!border-rose-400 dark:[&_input]:!border-rose-500 [&_input]:!ring-2 [&_input]:!ring-rose-500/25'
                                : ''" />

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                            {{-- Skema --}}
                            <x-select
                                wire:model.live="form.research_scheme_id"
                                label="Scheme"
                                required
                                size="lg"
                                color="emerald"
                                :class="$errors->has('form.research_scheme_id')
                                    ? '[&_select]:!border-rose-400 dark:[&_select]:!border-rose-500 [&_select]:!ring-2 [&_select]:!ring-rose-500/25'
                                    : ''">
                                <option value="">— Select Scheme —</option>
                                @foreach ($schemes as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->code }})</option>
                                @endforeach
                            </x-select>

                            {{-- Periode (readonly) --}}
                            <div>
                                <label class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                                    {{ __("Period") }}
                                </label>

                                @if ($activePeriod)
                                    <div @class([
                                        'flex items-center gap-2 rounded-full border py-1.5 px-3',
                                        'border-emerald-200 dark:border-emerald-800 bg-emerald-50/60 dark:bg-emerald-900/20'
                                            => ! $errors->has('form.period_id'),
                                        'border-rose-300 dark:border-rose-700 bg-rose-50/60 dark:bg-rose-900/20 ring-2 ring-rose-500/25'
                                            => $errors->has('form.period_id'),
                                    ])>
                                        <flux:icon.calendar-days
                                            class="size-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                        <div class="min-w-0 flex-1">
                                            <p class="text-[11px] font-semibold text-emerald-800 dark:text-emerald-300 truncate">
                                                {{ $activePeriod->periode }}
                                            </p>
                                        </div>
                                    </div>

                                    <input type="hidden" wire:model="form.period_id" />
                                @else
                                    <div class="flex items-center gap-2 rounded-full border py-1.5 px-3
                                                border-rose-300 dark:border-rose-700
                                                bg-rose-50/60 dark:bg-rose-900/20
                                                ring-2 ring-rose-500/25">
                                        <flux:icon.exclamation-triangle
                                            class="size-3.5 text-rose-600 dark:text-rose-400 shrink-0" />
                                        <p class="text-[11px] font-medium text-rose-700 dark:text-rose-300">
                                            {{ __("No active period!") }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Kata Kunci --}}
                        <x-input
                            wire:model="form.keywords"
                            label="Keyword"
                            required
                            placeholder="Keyword 1; Keyword 2"
                            :class="$errors->has('form.keywords')
                                ? '[&_input]:!border-rose-400 dark:[&_input]:!border-rose-500 [&_input]:!ring-2 [&_input]:!ring-rose-500/25'
                                : ''" />

                        {{-- Ringkasan --}}
                        <x-textarea
                            wire:model="form.summary"
                            label="Summary"
                            required
                            rows="4"
                            color="emerald"
                            placeholder="Propsal summary..."
                            :class="$errors->has('form.summary')
                                ? '[&_textarea]:!border-rose-400 dark:[&_textarea]:!border-rose-500 [&_textarea]:!ring-2 [&_textarea]:!ring-rose-500/25'
                                : ''" />

                        {{-- ═════════ PROPOSAL MEMBER (DOSEN) ═════════ --}}
                        <div class="rounded-2xl border border-slate-200 dark:border-zinc-700
                                    bg-slate-50/50 dark:bg-zinc-800/30 overflow-hidden">

                            <div class="px-3 py-2.5 border-b border-slate-200 dark:border-zinc-700
                                        flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 min-w-0">
                                    <flux:icon.user-group class="size-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                    <span class="text-[11.5px] font-semibold text-slate-800 dark:text-zinc-200">
                                        {{ __("Proposal Member") }}
                                    </span>
                                    @if (count($pendingMembers) > 0)
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full
                                                     bg-emerald-100 text-emerald-700
                                                     dark:bg-emerald-900/40 dark:text-emerald-300">
                                            {{ count($pendingMembers) }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="p-3 space-y-2.5">

                                <div class="relative">
                                    <flux:icon.magnifying-glass
                                        class="absolute left-3 top-1/2 -translate-y-1/2 size-3.5
                                               text-slate-400 pointer-events-none" />
                                    <input type="text"
                                        wire:model.live.debounce.200ms="memberSearch"
                                        placeholder="Search NIDN or Full Name..."
                                        class="block w-full rounded-full shadow-sm text-[11px] pl-9 pr-3.5
                                               border border-slate-200 dark:border-zinc-700
                                               bg-white dark:bg-zinc-800
                                               text-slate-900 dark:text-zinc-100
                                               placeholder:text-slate-400 dark:placeholder:text-zinc-500
                                               py-1.5
                                               hover:shadow-sm hover:shadow-emerald-500/20 dark:hover:shadow-emerald-500/20
                                               hover:border-slate-300 dark:hover:border-zinc-600
                                               focus:outline-none
                                               focus:ring-2 focus:ring-emerald-500/25 focus:border-emerald-500
                                               focus:shadow-md focus:shadow-emerald-500/10
                                               transition-all duration-200" />
                                </div>

                                @if ($memberSuggestions->isNotEmpty())
                                    <div class="rounded-xl border border-slate-200 dark:border-zinc-700
                                                bg-white dark:bg-zinc-800 overflow-hidden">
                                        @foreach ($memberSuggestions as $u)
                                            <button type="button"
                                                wire:key="member-suggest-{{ $u->id }}"
                                                wire:click="selectMember({{ $u->id }})"
                                                class="w-full flex items-center gap-2.5 px-2.5 py-2
                                                       text-left transition-colors
                                                       hover:bg-emerald-50 dark:hover:bg-emerald-900/20
                                                       {{ ! $loop->last ? 'border-b border-slate-100 dark:border-zinc-700/70' : '' }}">
                                                <div class="w-7 h-7 rounded-full shrink-0
                                                            bg-emerald-100 dark:bg-emerald-900/40
                                                            flex items-center justify-center
                                                            text-[10px] font-bold
                                                            text-emerald-700 dark:text-emerald-300">
                                                    {{ strtoupper(substr($u->full_name, 0, 1)) }}
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-[11px] font-medium truncate
                                                              text-slate-900 dark:text-zinc-100">
                                                        {{ $u->full_name }}
                                                    </p>
                                                    <p class="text-[9.5px] truncate text-slate-500 dark:text-zinc-500">
                                                        {{ $u->nidn ?? '—' }} · {{ $u->email }}
                                                    </p>
                                                </div>
                                                <flux:icon.plus-circle class="size-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                            </button>
                                        @endforeach
                                    </div>
                                @endif

                                @if (count($pendingMembers) > 0)
                                    <div class="space-y-1.5 pt-1">
                                        @foreach ($pendingMembers as $index => $m)
                                            <div wire:key="pending-member-{{ $index }}"
                                                class="flex items-center gap-2.5 px-2.5 py-1.5
                                                       rounded-full bg-white dark:bg-zinc-800
                                                       border border-slate-200 dark:border-zinc-700">
                                                <div class="w-6 h-6 rounded-full shrink-0
                                                            bg-emerald-100 dark:bg-emerald-900/40
                                                            flex items-center justify-center
                                                            text-[9px] font-bold
                                                            text-emerald-700 dark:text-emerald-300">
                                                    {{ strtoupper(substr($m['name'], 0, 1)) }}
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-[11px] font-medium truncate
                                                              text-slate-900 dark:text-zinc-100">
                                                        {{ $m['name'] }}
                                                    </p>
                                                    <p class="text-[9.5px] text-slate-500 dark:text-zinc-500 truncate">
                                                        {{ $m['nidn'] ?? '—' }}
                                                    </p>
                                                </div>
                                                <span class="text-[9px] font-semibold px-1.5 py-0.5 rounded-full
                                                             bg-sky-100 text-sky-700
                                                             dark:bg-sky-900/40 dark:text-sky-300 shrink-0">
                                                    {{ __("Member") }}
                                                </span>
                                                <button type="button"
                                                    wire:click="removePendingMember({{ $index }})"
                                                    aria-label="Hapus"
                                                    class="shrink-0 w-5 h-5 rounded-full flex items-center justify-center
                                                           text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30
                                                           hover:scale-110 active:scale-95
                                                           transition-all duration-150">
                                                    <flux:icon.x-mark class="size-3" />
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-[10px] text-slate-400 dark:text-zinc-500 italic pl-1">
                                        {{ __("Faculty members have not yet been added.") }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        {{-- ═════════ PROPOSAL STUDENT (MAHASISWA) ═════════ --}}
                        <div class="rounded-2xl border border-slate-200 dark:border-zinc-700
                                    bg-slate-50/50 dark:bg-zinc-800/30 overflow-hidden">

                            <div class="px-3 py-2.5 border-b border-slate-200 dark:border-zinc-700
                                        flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 min-w-0">
                                    <flux:icon.academic-cap class="size-3.5 text-violet-600 dark:text-violet-400 shrink-0" />
                                    <span class="text-[11.5px] font-semibold text-slate-800 dark:text-zinc-200">
                                        {{ __("Proposal Student") }}
                                    </span>
                                    @if (count($pendingStudents) > 0)
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full
                                                     bg-violet-100 text-violet-700
                                                     dark:bg-violet-900/40 dark:text-violet-300">
                                            {{ count($pendingStudents) }}
                                        </span>
                                    @endif
                                </div>

                                <button type="button" wire:click="toggleStudentForm"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full
                                           text-[10px] font-semibold
                                           {{ $showStudentForm
                                               ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300'
                                               : 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300' }}
                                           hover:scale-[1.03] active:scale-[0.97]
                                           transition-all duration-150">
                                    <flux:icon :name="$showStudentForm ? 'x-mark' : 'plus'" class="size-2.5" />
                                    {{ $showStudentForm ? 'Batal' : 'Add' }}
                                </button>
                            </div>

                            <div class="p-3 space-y-2.5">

                                @if ($showStudentForm)
                                    <div class="p-3 rounded-xl
                                                bg-white dark:bg-zinc-800
                                                border border-violet-200 dark:border-violet-800/60
                                                space-y-2.5">

                                        <div class="grid grid-cols-1 sm:grid-cols-[180px_1fr] gap-2.5">
                                            <div>
                                                <x-input
                                                    wire:model="studentForm.nim"
                                                    label="NIM"
                                                    required
                                                    placeholder="IST2104006"
                                                    :class="$errors->has('studentForm.nim')
                                                        ? '[&_input]:border-rose-400! dark:[&_input]:border-rose-500! [&_input]:ring-2! [&_input]:ring-rose-500/25!'
                                                        : ''" />
                                            </div>

                                            <div>
                                                <x-input
                                                    wire:model="studentForm.name"
                                                    label="Full Name"
                                                    required
                                                    placeholder="eg: John Doe"
                                                    :class="$errors->has('studentForm.name')
                                                        ? '[&_input]:!border-rose-400 dark:[&_input]:!border-rose-500 [&_input]:!ring-2 [&_input]:!ring-rose-500/25'
                                                        : ''" />
                                            </div>
                                        </div>

                                        <div>
                                            <x-input
                                                wire:model="studentForm.program_study"
                                                label="Study Program"
                                                required
                                                placeholder="eg: Teknologi Informasi"
                                                :class="$errors->has('studentForm.program_study')
                                                    ? '[&_input]:!border-rose-400 dark:[&_input]:!border-rose-500 [&_input]:!ring-2 [&_input]:!ring-rose-500/25'
                                                    : ''" />
                                        </div>

                                        <div class="flex justify-end pt-1">
                                            <button type="button" wire:click="addStudent"
                                                wire:loading.attr="disabled"
                                                wire:target="addStudent"
                                                class="inline-flex items-center gap-1.5
                                                       px-3 py-1.5 rounded-full
                                                       text-[11px] font-semibold text-white
                                                       bg-violet-600/90 hover:bg-violet-600
                                                       shadow-sm shadow-violet-500/20 hover:shadow-sm hover:shadow-violet-500/30
                                                       hover:scale-[1.02] active:scale-[0.97]
                                                       disabled:opacity-60 disabled:cursor-wait disabled:hover:scale-100
                                                       transition-all duration-150">
                                                <svg wire:loading wire:target="addStudent"
                                                     class="animate-spin size-3" viewBox="0 0 24 24" fill="none">
                                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/>
                                                    <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                                                </svg>
                                                <flux:icon.plus wire:loading.remove wire:target="addStudent" class="size-3" />
                                                <span wire:loading.remove wire:target="addStudent">{{ __("Add Student") }}</span>
                                                <span wire:loading wire:target="addStudent">{{ __("Saving...") }}</span>
                                            </button>
                                        </div>
                                    </div>
                                @endif

                                @if (count($pendingStudents) > 0)
                                    <div class="space-y-1.5">
                                        @foreach ($pendingStudents as $index => $s)
                                            <div wire:key="pending-student-{{ $index }}"
                                                class="flex items-center gap-2.5 px-2.5 py-1.5
                                                       rounded-full bg-white dark:bg-zinc-800
                                                       border border-slate-200 dark:border-zinc-700">
                                                <div class="w-6 h-6 rounded-full shrink-0
                                                            bg-violet-100 dark:bg-violet-900/40
                                                            flex items-center justify-center
                                                            text-[9px] font-bold
                                                            text-violet-700 dark:text-violet-300">
                                                    {{ strtoupper(substr($s['name'], 0, 1)) }}
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-[11px] font-medium truncate
                                                              text-slate-900 dark:text-zinc-100">
                                                        {{ $s['name'] }}
                                                    </p>
                                                    <p class="text-[9.5px] text-slate-500 dark:text-zinc-500 truncate">
                                                        {{ $s['nim'] }} · {{ $s['program_study'] }}
                                                    </p>
                                                </div>
                                                <span class="text-[9px] font-semibold px-1.5 py-0.5 rounded-full
                                                             bg-sky-100 text-sky-700
                                                             dark:bg-sky-900/40 dark:text-sky-300 shrink-0">
                                                    {{ __("Member") }}
                                                </span>
                                                <button type="button"
                                                    wire:click="removePendingStudent({{ $index }})"
                                                    aria-label="Hapus"
                                                    class="shrink-0 w-5 h-5 rounded-full flex items-center justify-center
                                                           text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30
                                                           hover:scale-110 active:scale-95
                                                           transition-all duration-150">
                                                    <flux:icon.x-mark class="size-3" />
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif (! $showStudentForm)
                                    <p class="text-[10px] text-slate-400 dark:text-zinc-500 italic pl-1">
                                        {{ __("No students have been added yet.") }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ═══════════════════════════════════════════════ --}}
                {{-- STEP 2: ANGGARAN + RAB                           --}}
                {{-- ═══════════════════════════════════════════════ --}}
                @if ($step === 2)
                    <div class="space-y-4">

                        {{-- Budget summary card --}}
                        <div class="rounded-2xl border border-slate-200 dark:border-zinc-700
                                    bg-slate-50 dark:bg-zinc-800/40 p-3">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[11px] font-medium text-slate-700 dark:text-zinc-300">
                                    {{ __("Budget Total") }}
                                </span>
                                <span class="text-[11px] font-bold text-slate-900 dark:text-white">
                                    Rp {{ number_format($budgetTotal, 0, ',', '.') }}
                                    <span class="text-slate-400 font-normal">
                                        / Rp {{ number_format($budgetLimit, 0, ',', '.') }}
                                    </span>
                                </span>
                            </div>
                            <div class="h-1.5 rounded-full bg-slate-200 dark:bg-zinc-700 overflow-hidden">
                                <div class="h-full rounded-full bg-{{ $theme['accent'] }}-500 transition-all"
                                    style="width: {{ $budgetPercent }}%"></div>
                            </div>
                            <p class="mt-1.5 text-[10px] text-slate-500 dark:text-zinc-400">
                                 {{ __("Remaining: Rp ") . number_format($budgetRemaining, 0, ',', '.') }}
                            </p>
                        </div>

                        {{-- Add budget item --}}
                        <div class="grid grid-cols-1 sm:grid-cols-[1fr_140px_auto] gap-2 items-end">
                            <x-input
                                wire:model="budgetForm.item_name"
                                label="Component Item"
                                placeholder="Contoh: Honorarium"
                                :class="$errors->has('budgetForm.item_name')
                                    ? '[&_input]:!border-rose-400 dark:[&_input]:!border-rose-500 [&_input]:!ring-2 [&_input]:!ring-rose-500/25'
                                    : ''" />

                            <x-input
                                wire:model="budgetForm.amount"
                                label="Total (Rp)"
                                placeholder="1000000"
                                :class="$errors->has('budgetForm.amount')
                                    ? '[&_input]:!border-rose-400 dark:[&_input]:!border-rose-500 [&_input]:!ring-2 [&_input]:!ring-rose-500/25'
                                    : ''" />

                            <flux:button type="button" wire:click="addBudgetItem" icon="plus" variant="primary"
                                class="rounded-full">
                                Add
                            </flux:button>
                        </div>

                        {{-- Budget items list --}}
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            @forelse ($pendingBudgetItems as $index => $item)
                                <div wire:key="budget-{{ $index }}"
                                    class="flex items-center justify-between gap-3 p-2.5 rounded-full
                                            border border-slate-200 dark:border-zinc-700
                                            bg-white dark:bg-zinc-800/40">
                                    <div class="flex-1 min-w-0 pl-1.5">
                                        <p class="text-[12px] font-medium text-slate-900 dark:text-zinc-100 truncate">
                                            {{ $item['item_name'] }}
                                        </p>
                                        <p class="text-[10px] text-slate-500 dark:text-zinc-400">
                                            Rp {{ number_format($item['amount'], 0, ',', '.') }}
                                        </p>
                                    </div>
                                    <button type="button" wire:click="removeBudgetItem({{ $index }})"
                                        class="p-1.5 rounded-full text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30
                                               hover:scale-110 active:scale-95 transition-all duration-150">
                                        <flux:icon.trash class="size-3.5" />
                                    </button>
                                </div>
                            @empty
                                <p class="text-[11px] text-center text-slate-400 dark:text-zinc-500 italic py-4">
                                    {{ __("No budget item found!") }}
                                </p>
                            @endforelse
                        </div>

                        {{-- ═════════ FILE RAB (XLSX) ═════════ --}}
                        <div>
                            <x-input
                                type="file"
                                wire:model="form.rab_file"
                                label="RAB File"
                                required
                                accept=".xlsx"
                                hint="Max 10 MB · Format XLSX"
                                :class="$errors->has('form.rab_file')
                                    ? '[&_input]:!border-rose-400 dark:[&_input]:!border-rose-500 [&_input]:!ring-2 [&_input]:!ring-rose-500/25'
                                    : ''" />
                        </div>
                    </div>
                @endif
            </div>

            {{-- FOOTER --}}
            <div class="shrink-0 flex flex-col-reverse sm:flex-row sm:justify-between gap-2
                        px-5 sm:px-6 py-4 border-t border-slate-200 dark:border-zinc-700
                        bg-slate-50/50 dark:bg-zinc-900/50 rounded-b-3xl sm:rounded-b-3xl">

                <button type="button" @click="show = false"
                    class="w-full sm:w-auto px-3 py-1.5 text-[11px] font-medium rounded-full
                           text-slate-700 dark:text-zinc-300
                           bg-white dark:bg-zinc-800
                           border border-slate-300 dark:border-zinc-600
                           hover:bg-slate-50 dark:hover:bg-zinc-700
                           shadow-sm shadow-zinc-200/40 hover:shadow-sm hover:shadow-rose-500/15
                           hover:scale-[1.02] active:scale-[0.97]
                           transition-all duration-150">
                    {{ __("Cancel") }}
                </button>

                <div class="flex flex-col-reverse sm:flex-row gap-1.5">
                    @if ($step === 2)
                        <flux:button type="button" wire:click="prevStep">
                            {{ __("Previous") }}
                        </flux:button>
                    @endif

                    @if ($step === 1)
                        <flux:button type="button" wire:click="nextStep" variant="primary"
                            wire:loading.attr="disabled" wire:target="nextStep">
                            {{ __("Next Step") }}
                        </flux:button>
                    @else
                        <button type="button" wire:click="finish"
                            wire:loading.attr="disabled" wire:target="finish"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5
                                   px-3 py-1.5 text-[11px] font-medium rounded-full text-white
                                   bg-{{ $theme['accent'] }}-600/90 hover:bg-{{ $theme['accent'] }}-600
                                   shadow-sm shadow-{{ $theme['accent'] }}-500/20 hover:shadow-sm hover:shadow-{{ $theme['accent'] }}-500/30
                                   disabled:opacity-60 disabled:cursor-wait disabled:hover:scale-100
                                   hover:scale-[1.02] active:scale-[0.97]
                                   transition-all duration-150">
                            <svg wire:loading wire:target="finish" class="animate-spin size-3" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/>
                                <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                            </svg>
                            <span wire:loading.remove wire:target="finish">{{ __("Submit Proposal") }}</span>
                            <span wire:loading wire:target="finish">{{ __("Saving...") }}</span>
                        </button>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>
