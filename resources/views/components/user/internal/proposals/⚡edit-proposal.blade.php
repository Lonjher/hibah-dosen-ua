<?php

use App\Livewire\Forms\BudgetProposalForm;
use App\Livewire\Forms\ProposalForm;
use App\Models\BudgetProposal;
use App\Models\Period;
use App\Models\Proposal;
use App\Models\ResearchScheme;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ProposalForm $form;
    public BudgetProposalForm $budgetForm;

    public int $step = 1;

    #[On('open-edit-proposal')]
    public function load(int $id): void
    {
        // Ganti findOrFail → find, supaya tidak throw 404
        $proposal = Proposal::where('user_id', auth()->id())->find($id);

        // Handle: proposal tidak ditemukan / bukan milik user
        if (!$proposal) {
            Flux::toast('Proposal tidak ditemukan atau bukan milik Anda.', variant: 'danger');
            return;
        }

        // Handle: status tidak boleh diedit
        if (!in_array($proposal->status, ['pending', 'revised'])) {
            Flux::toast("Proposal dengan status '{$proposal->status}' tidak dapat diedit.", variant: 'danger');
            return;
        }

        $this->form->setProposal($proposal);
        $this->budgetForm->proposal_id = $proposal->id;
        $this->budgetForm->reset('item_name', 'amount');
        $this->step = 1;
        $this->resetErrorBag();
        $this->resetValidation();

        $this->dispatch('show-edit-proposal');
    }

    public function nextStep(): void
    {
        $this->form->validateStep1();

        // ── Handle file proposal (jika user upload file baru) ──
        $newFilePath = $this->form->storeFile();

        $updateData = [
            'research_scheme_id' => $this->form->research_scheme_id,
            'title'              => $this->form->title,
            'summary'            => $this->form->summary,
            'keywords'           => $this->form->keywords,
            'period_id'          => $this->form->period_id,
        ];

        if ($newFilePath) {
            // Hapus file lama kalau ada
            if ($this->form->proposal->file_path
                && \Storage::disk('public')->exists($this->form->proposal->file_path)) {
                \Storage::disk('public')->delete($this->form->proposal->file_path);
            }

            $updateData['file_path'] = $newFilePath;
            $this->form->file_path = $newFilePath;
        }

        $this->form->proposal->update($updateData);

        // Reset file property (sudah di-store)
        $this->form->file = null;

        $this->step = 2;
    }

    public function prevStep(): void
    {
        $this->step = 1;
    }

    public function addBudgetItem(): void
    {
        if (!$this->form->proposal) {
            return;
        }

        $this->budgetForm->proposal_id = $this->form->proposal->id;
        $this->budgetForm->validate();

        $amount = (int) preg_replace('/\D/', '', (string) $this->budgetForm->amount);
        $proposal = $this->form->proposal->load('researchScheme');
        $limit = (int) ($proposal->researchScheme?->budget_limit ?? 0);
        $current = (int) BudgetProposal::where('proposal_id', $proposal->id)->sum('amount');

        if ($current + $amount > $limit) {
            $this->budgetForm->addError('amount', 'Melebihi batas anggaran.');
            return;
        }

        $this->budgetForm->amount = $amount;
        $this->budgetForm->create();

        $this->budgetForm->reset('item_name', 'amount');
        $this->budgetForm->proposal_id = $this->form->proposal->id;

        Flux::toast('Item anggaran ditambahkan.', variant: 'success');
    }

    public function removeBudgetItem(int $id): void
    {
        if (!$this->form->proposal) {
            return;
        }
        BudgetProposal::where('proposal_id', $this->form->proposal->id)
            ->where('id', $id)
            ->delete();
        Flux::toast('Item anggaran dihapus.', variant: 'success');
    }

    public function save(): void
    {
        if (!$this->form->proposal) {
            return;
        }

        $total = (int) BudgetProposal::where('proposal_id', $this->form->proposal->id)->sum('amount');

        if ($total <= 0) {
            Flux::toast('Tambahkan minimal 1 item anggaran.', variant: 'danger');
            return;
        }

        $this->form->update();

        Flux::toast('Proposal berhasil diperbarui.', variant: 'success');
        $this->dispatch('proposal-updated', message: 'Proposal berhasil diperbarui.');
        $this->resetAll();
    }

    public function resetAll(): void
    {
        $this->form->reset();
        $this->budgetForm->reset();
        $this->step = 1;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function with(): array
    {
        $budgetItems = collect();
        $budgetTotal = 0;
        $budgetLimit = 0;

        if ($this->form->proposal) {
            $proposal = $this->form->proposal->load('researchScheme');
            $budgetItems = BudgetProposal::where('proposal_id', $proposal->id)->orderBy('id')->get();
            $budgetTotal = (int) $budgetItems->sum('amount');
            $budgetLimit = (int) ($proposal->researchScheme?->budget_limit ?? 0);
        } elseif ($this->form->research_scheme_id) {
            $budgetLimit = (int) (ResearchScheme::find($this->form->research_scheme_id)?->budget_limit ?? 0);
        }

        return [
            'schemes' => ResearchScheme::where('is_active', true)->orderBy('name')->get(),
            'periods' => Period::orderByDesc('periode')->get(),
            'budgetItems' => $budgetItems,
            'budgetTotal' => $budgetTotal,
            'budgetLimit' => $budgetLimit,
            'budgetRemaining' => $budgetLimit - $budgetTotal,
            'budgetPercent' => $budgetLimit > 0 ? min(round(($budgetTotal / $budgetLimit) * 100, 1), 100) : 0,
        ];
    }
};
?>

<div x-data="{
    show: false,
    errorMessage: '',
    init() {
        window.addEventListener('show-edit-proposal', () => {
            this.errorMessage = '';
            this.show = true;
        });
        window.addEventListener('proposal-error', (e) => { this.errorMessage = e.detail.message; });
        window.addEventListener('proposal-updated', () => { this.show = false; });
    }
}" x-show="show" x-transition.opacity x-cloak
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4" @click.self="show = false">

    <div class="flex max-h-[90vh] w-full sm:max-w-2xl flex-col overflow-hidden
                bg-white dark:bg-zinc-900 rounded-t-3xl sm:rounded-3xl shadow-2xl
                border border-slate-200 dark:border-zinc-700"
        @click.stop>

        <form class="flex min-h-0 flex-1 flex-col">

            {{-- HEADER blue --}}
            <div class="shrink-0 bg-gradient-to-r from-blue-600 to-blue-500
                        px-5 sm:px-6 py-4 rounded-t-3xl sm:rounded-t-3xl">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                        <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                            <flux:icon.pencil-square class="size-4 text-white" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-heading text-[15px] font-semibold text-white leading-tight">
                                Edit Proposal
                            </h3>
                            <p class="text-[11px] text-white/75 mt-0.5">
                                Step {{ $step }} dari 2 — {{ $step === 1 ? 'Metadata' : 'Anggaran' }}
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
                                    Mohon periksa kembali:
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

                @if ($step === 1)
                    <div class="space-y-4">

                        {{-- ══════════ CURRENT FILE (custom) ══════════ --}}
                        @if ($form->proposal?->file_path)
                            <div class="rounded-2xl border border-slate-200 dark:border-zinc-700/70
                                        bg-slate-50 dark:bg-zinc-800/40 px-3 py-2.5 space-y-2">

                                <p class="text-[9.5px] uppercase tracking-wider font-semibold
                                          text-slate-500 dark:text-zinc-400 leading-none">
                                    Current File
                                </p>

                                <div class="flex items-center gap-2 pl-1 pr-0.5 py-1
                                            rounded-full bg-white dark:bg-zinc-900/60
                                            border border-slate-200 dark:border-zinc-700/70">
                                    <div class="w-6 h-6 rounded-full shrink-0
                                                bg-blue-100 dark:bg-blue-900/40
                                                flex items-center justify-center">
                                        <flux:icon.document-text class="size-3 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[9px] uppercase tracking-wider font-semibold
                                                  text-blue-600 dark:text-blue-400 leading-none">
                                            Proposal
                                        </p>
                                        <p class="mt-0.5 text-[10.5px] font-medium truncate
                                                  text-slate-800 dark:text-zinc-200"
                                            title="{{ basename($form->proposal->file_path) }}">
                                            {{ basename($form->proposal->file_path) }}
                                        </p>
                                    </div>
                                    <a href="{{ Storage::disk('public')->url($form->proposal->file_path) }}"
                                        target="_blank" aria-label="View file"
                                        class="shrink-0 w-6 h-6 rounded-full flex items-center justify-center
                                               text-blue-600 dark:text-blue-400
                                               hover:bg-blue-50 dark:hover:bg-blue-900/40
                                               hover:scale-110 active:scale-95
                                               transition-all duration-150">
                                        <flux:icon.arrow-up-right class="size-3" />
                                    </a>
                                </div>
                            </div>
                        @endif

                        {{-- ══════════ FILE INPUT (optional, untuk replace) ══════════ --}}
                        <x-input
                            type="file"
                            wire:model="form.file"
                            label="Replace File Proposal (optional)"
                            accept=".pdf,.doc,.docx"
                            hint="Biarkan kosong untuk tetap pakai file lama · Maks 10 MB"
                            :class="$errors->has('form.file')
                                ? '[&_input]:!border-rose-400 dark:[&_input]:!border-rose-500 [&_input]:!ring-2 [&_input]:!ring-rose-500/25'
                                : ''" />

                        {{-- Judul --}}
                        <div>
                            <x-input
                                wire:model="form.title"
                                label="Judul"
                                required
                                placeholder="Judul proposal"
                                :class="$errors->has('form.title')
                                    ? '[&_input]:!border-rose-400 dark:[&_input]:!border-rose-500 [&_input]:!ring-2 [&_input]:!ring-rose-500/25'
                                    : ''" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                            {{-- Skema --}}
                            <x-select
                                wire:model.live="form.research_scheme_id"
                                label="Skema"
                                required
                                size="lg"
                                color="blue"
                                :class="$errors->has('form.research_scheme_id')
                                    ? '[&_select]:!border-rose-400 dark:[&_select]:!border-rose-500 [&_select]:!ring-2 [&_select]:!ring-rose-500/25'
                                    : ''">
                                @foreach ($schemes as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->code }})</option>
                                @endforeach
                            </x-select>

                            {{-- Periode --}}
                            <x-select
                                wire:model="form.period_id"
                                label="Periode"
                                required
                                size="lg"
                                color="blue"
                                :class="$errors->has('form.period_id')
                                    ? '[&_select]:!border-rose-400 dark:[&_select]:!border-rose-500 [&_select]:!ring-2 [&_select]:!ring-rose-500/25'
                                    : ''">
                                @foreach ($periods as $p)
                                    <option value="{{ $p->id }}">{{ $p->periode }}</option>
                                @endforeach
                            </x-select>
                        </div>

                        {{-- Kata Kunci --}}
                        <div>
                            <x-input
                                wire:model="form.keywords"
                                label="Kata Kunci"
                                required
                                placeholder="Keyword 1; Keyword 2"
                                :class="$errors->has('form.keywords')
                                    ? '[&_input]:!border-rose-400 dark:[&_input]:!border-rose-500 [&_input]:!ring-2 [&_input]:!ring-rose-500/25'
                                    : ''" />
                        </div>

                        {{-- Ringkasan --}}
                        <div>
                            <x-textarea
                                wire:model="form.summary"
                                label="Ringkasan"
                                required
                                rows="4"
                                color="blue"
                                placeholder="Ringkasan proposal..."
                                :class="$errors->has('form.summary')
                                    ? '[&_textarea]:!border-rose-400 dark:[&_textarea]:!border-rose-500 [&_textarea]:!ring-2 [&_textarea]:!ring-rose-500/25'
                                    : ''" />
                        </div>
                    </div>
                @endif

                @if ($step === 2)
                    <div class="space-y-4">

                        {{-- Budget summary card --}}
                        <div class="rounded-2xl border border-slate-200 dark:border-zinc-700
                                    bg-slate-50 dark:bg-zinc-800/40 p-3">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[11px] font-medium text-slate-700 dark:text-zinc-300">Total Anggaran</span>
                                <span class="text-[11px] font-bold text-slate-900 dark:text-white">
                                    Rp {{ number_format($budgetTotal, 0, ',', '.') }}
                                    <span class="text-slate-400 font-normal">
                                        / Rp {{ number_format($budgetLimit, 0, ',', '.') }}
                                    </span>
                                </span>
                            </div>
                            <div class="h-1.5 rounded-full bg-slate-200 dark:bg-zinc-700 overflow-hidden">
                                <div class="h-full rounded-full bg-blue-500 transition-all"
                                    style="width: {{ $budgetPercent }}%"></div>
                            </div>
                            <p class="mt-1.5 text-[10px] text-slate-500 dark:text-zinc-400">
                                Sisa: Rp {{ number_format($budgetRemaining, 0, ',', '.') }}
                            </p>
                        </div>

                        {{-- Add budget item --}}
                        <div class="grid grid-cols-1 sm:grid-cols-[1fr_140px_auto] gap-2 items-end">
                            <x-input
                                wire:model="budgetForm.item_name"
                                label="Nama Item"
                                placeholder="Contoh: Honorarium"
                                :class="$errors->has('budgetForm.item_name')
                                    ? '[&_input]:!border-rose-400 dark:[&_input]:!border-rose-500 [&_input]:!ring-2 [&_input]:!ring-rose-500/25'
                                    : ''" />
                            <x-input
                                wire:model="budgetForm.amount"
                                label="Jumlah (Rp)"
                                placeholder="1000000"
                                :class="$errors->has('budgetForm.amount')
                                    ? '[&_input]:!border-rose-400 dark:[&_input]:!border-rose-500 [&_input]:!ring-2 [&_input]:!ring-rose-500/25'
                                    : ''" />
                            <button type="button" wire:click="addBudgetItem"
                                class="inline-flex items-center justify-center gap-1.5
                                       px-3 py-1.5 text-[11px] font-medium rounded-full text-white
                                       bg-blue-600/90 hover:bg-blue-600
                                       shadow-sm shadow-blue-500/20 hover:shadow-sm hover:shadow-blue-500/30
                                       hover:scale-[1.02] active:scale-[0.97]
                                       transition-all duration-150">
                                <flux:icon.plus class="size-3.5" />
                                Add
                            </button>
                        </div>

                        {{-- Budget items list --}}
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            @forelse ($budgetItems as $item)
                                <div class="flex items-center justify-between gap-3 p-2.5 rounded-full
                                            border border-slate-200 dark:border-zinc-700
                                            bg-white dark:bg-zinc-800/40">
                                    <div class="flex-1 min-w-0 pl-1.5">
                                        <p class="text-[12px] font-medium text-slate-900 dark:text-zinc-100 truncate">
                                            {{ $item->item_name }}
                                        </p>
                                        <p class="text-[10px] text-slate-500 dark:text-zinc-400">
                                            Rp {{ number_format($item->amount, 0, ',', '.') }}
                                        </p>
                                    </div>
                                    <button type="button" wire:click="removeBudgetItem({{ $item->id }})"
                                        wire:confirm="Hapus item ini?"
                                        class="p-1.5 rounded-full text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30
                                               hover:scale-110 active:scale-95 transition-all duration-150">
                                        <flux:icon.trash class="size-3.5" />
                                    </button>
                                </div>
                            @empty
                                <p class="text-[11px] text-center text-slate-400 dark:text-zinc-500 italic py-4">
                                    Belum ada item anggaran.
                                </p>
                            @endforelse
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
                           shadow-sm shadow-zinc-200/40 hover:shadow-sm hover:shadow-blue-500/15
                           hover:scale-[1.02] active:scale-[0.97]
                           transition-all duration-150">
                    Cancel
                </button>

                <div class="flex flex-col-reverse sm:flex-row gap-1.5">
                    @if ($step === 2)
                        <button type="button" wire:click="prevStep"
                            class="w-full sm:w-auto px-3 py-1.5 text-[11px] font-medium rounded-full
                                   text-slate-700 dark:text-zinc-300
                                   bg-white dark:bg-zinc-800
                                   border border-slate-300 dark:border-zinc-600
                                   hover:bg-slate-50 dark:hover:bg-zinc-700
                                   shadow-sm shadow-zinc-200/40 hover:shadow-sm hover:shadow-blue-500/15
                                   hover:scale-[1.02] active:scale-[0.97]
                                   transition-all duration-150">
                            Previous
                        </button>
                    @endif

                    @if ($step === 1)
                        <button type="button" wire:click="nextStep"
                            wire:loading.attr="disabled" wire:target="nextStep"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5
                                   px-3 py-1.5 text-[11px] font-medium rounded-full text-white
                                   bg-blue-600/90 hover:bg-blue-600
                                   shadow-sm shadow-blue-500/20 hover:shadow-sm hover:shadow-blue-500/30
                                   disabled:opacity-60 disabled:cursor-wait disabled:hover:scale-100
                                   hover:scale-[1.02] active:scale-[0.97]
                                   transition-all duration-150">
                            Next Step
                        </button>
                    @else
                        <button type="button" wire:click="save"
                            wire:loading.attr="disabled" wire:target="save"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5
                                   px-3 py-1.5 text-[11px] font-medium rounded-full text-white
                                   bg-blue-600/90 hover:bg-blue-600
                                   shadow-sm shadow-blue-500/20 hover:shadow-sm hover:shadow-blue-500/30
                                   disabled:opacity-60 disabled:cursor-wait disabled:hover:scale-100
                                   hover:scale-[1.02] active:scale-[0.97]
                                   transition-all duration-150">
                            <svg wire:loading wire:target="save" class="animate-spin size-3" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/>
                                <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                            </svg>
                            <span wire:loading.remove wire:target="save">Update Proposal</span>
                            <span wire:loading wire:target="save">Menyimpan...</span>
                        </button>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>
