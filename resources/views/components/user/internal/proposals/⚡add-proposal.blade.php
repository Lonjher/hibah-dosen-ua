<?php

use App\Livewire\Forms\BudgetProposalForm;
use App\Livewire\Forms\ProposalForm;
use App\Models\BudgetProposal;
use App\Models\Period;
use App\Models\Proposal;
use App\Models\ResearchScheme;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;
    public ProposalForm $form;
    public BudgetProposalForm $budgetForm;

    public bool $isResearch = true;
    public int $step = 1;
    public ?int $draftProposalId = null;

    protected function getActivePeriod(): ?Period
    {
        return Period::query()->where('is_active', true)->whereDate('open_from', '<=', now())->whereDate('open_to', '>=', now())->first();
    }

    public function mount(bool $isResearch = true): void
    {
        $this->isResearch = $isResearch;
        $this->form->is_research = $isResearch;
        $this->form->status = 'pending';

        // Auto-assign periode aktif (dalam rentang open_from - open_to)
        $activePeriod = $this->getActivePeriod();

        if ($activePeriod) {
            $this->form->period_id = $activePeriod->id;
        }
    }

    public function nextStep(): void
    {
        // Guard: pastikan periode aktif tersedia
        $activePeriod = $this->getActivePeriod();

        if (! $activePeriod) {
            Flux::toast('Tidak ada periode aktif. Hubungi admin.', variant: 'danger');
            return;
        }

        $this->form->period_id = $activePeriod->id;
        $this->form->validateStep1();

        // ── Handle file proposal ──
        $newFilePath = $this->form->storeFile();

        // Kalau replace file lama, hapus dulu
        if ($newFilePath && $this->draftProposalId) {
            $oldPath = Proposal::find($this->draftProposalId)?->file_path;
            if ($oldPath && \Storage::disk('public')->exists($oldPath)) {
                \Storage::disk('public')->delete($oldPath);
            }
        }

        if ($this->draftProposalId) {
            $updateData = [
                'research_scheme_id' => $this->form->research_scheme_id,
                'title'              => $this->form->title,
                'summary'            => $this->form->summary,
                'keywords'           => $this->form->keywords,
                'period_id'          => $this->form->period_id,
            ];

            if ($newFilePath) {
                $updateData['file_path'] = $newFilePath;
            }

            Proposal::where('user_id', auth()->id())
                ->findOrFail($this->draftProposalId)
                ->update($updateData);
        } else {
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
                'file_path'          => $newFilePath ?? '',
            ]);

            $this->draftProposalId = $proposal->id;
            $this->form->user_id = auth()->id();
        }

        // Reset file property (sudah di-store ke disk)
        $this->form->file = null;

        $this->budgetForm->proposal_id = $this->draftProposalId;
        $this->step = 2;
    }

    public function prevStep(): void
    {
        $this->step = 1;
    }

    public function addBudgetItem(): void
    {
        if (!$this->draftProposalId) {
            return;
        }

        $this->budgetForm->proposal_id = $this->draftProposalId;
        $this->budgetForm->validate();

        $amount = (int) preg_replace('/\D/', '', (string) $this->budgetForm->amount);

        $proposal = Proposal::with('researchScheme')->find($this->draftProposalId);
        $limit = (int) ($proposal?->researchScheme?->budget_limit ?? 0);
        $current = (int) BudgetProposal::where('proposal_id', $this->draftProposalId)->sum('amount');

        if ($current + $amount > $limit) {
            $remaining = max($limit - $current, 0);
            $this->budgetForm->addError('amount', 'Melebihi batas anggaran. Maksimal: Rp ' . number_format($remaining, 0, ',', '.'));
            return;
        }

        $this->budgetForm->amount = $amount;
        $this->budgetForm->create();

        $this->budgetForm->reset('item_name', 'amount');
        $this->budgetForm->proposal_id = $this->draftProposalId;

        $this->resetErrorBag();
        $this->resetValidation();

        Flux::toast('Item anggaran ditambahkan.', variant: 'success');
    }

    public function removeBudgetItem(int $id): void
    {
        if (!$this->draftProposalId) {
            return;
        }

        BudgetProposal::where('proposal_id', $this->draftProposalId)->where('id', $id)->delete();

        Flux::toast('Item anggaran dihapus.', variant: 'success');
    }

    public function finish(): void
    {
        if (!$this->draftProposalId) {
            return;
        }

        $total = (int) BudgetProposal::where('proposal_id', $this->draftProposalId)->sum('amount');

        if ($total <= 0) {
            Flux::toast('Tambahkan minimal 1 item anggaran.', variant: 'danger');
            return;
        }

        $proposal = Proposal::with('researchScheme')->find($this->draftProposalId);
        $limit = (int) ($proposal?->researchScheme?->budget_limit ?? 0);

        if ($total > $limit) {
            Flux::toast('Total anggaran melebihi batas.', variant: 'danger');
            return;
        }

        Flux::toast('Proposal berhasil disimpan.', variant: 'success');
        $this->dispatch('proposal-added', message: 'Proposal berhasil ditambahkan.');
        $this->resetAll();
    }

    public function resetAll(): void
    {
        $this->form->reset();
        $this->budgetForm->reset();
        $this->reset(['step', 'draftProposalId']);
        $this->step = 1;
        $this->form->is_research = $this->isResearch;
        $this->form->status = 'pending';
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function with(): array
    {
        $budgetItems = collect();
        $budgetTotal = 0;
        $budgetLimit = 0;

        if ($this->draftProposalId) {
            $proposal = Proposal::with('researchScheme')->find($this->draftProposalId);
            $budgetItems = BudgetProposal::where('proposal_id', $this->draftProposalId)->orderBy('id')->get();
            $budgetTotal = (int) $budgetItems->sum('amount');
            $budgetLimit = (int) ($proposal?->researchScheme?->budget_limit ?? 0);
        } elseif ($this->form->research_scheme_id) {
            $budgetLimit = (int) (ResearchScheme::find($this->form->research_scheme_id)?->budget_limit ?? 0);
        }

        $theme = $this->isResearch ? ['icon' => 'document-plus', 'gradient' => 'from-emerald-600 to-emerald-500', 'label' => 'Penelitian', 'accent' => 'emerald'] : ['icon' => 'heart', 'gradient' => 'from-rose-600 to-rose-500', 'label' => 'Pengabdian', 'accent' => 'rose'];

        return [
            'schemes' => ResearchScheme::where('is_active', true)->orderBy('name')->get(),
            'budgetItems' => $budgetItems,
            'activePeriod' => $this->getActivePeriod(),
            'budgetTotal' => $budgetTotal,
            'budgetLimit' => $budgetLimit,
            'budgetRemaining' => $budgetLimit - $budgetTotal,
            'budgetPercent' => $budgetLimit > 0 ? min(round(($budgetTotal / $budgetLimit) * 100, 1), 100) : 0,
            'theme' => $theme,
        ];
    }
};
?>

<div x-data="{
    show: false,
    init() {
        window.addEventListener('open-add-proposal', () => {
            this.show = true;
        });
        window.addEventListener('proposal-added', () => { this.show = false; });
    }
}" x-show="show" x-transition.opacity x-cloak
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4" @click.self="show = false">

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
                                Add New {{ $theme['label'] }}
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

            {{-- ══════════ ERROR SUMMARY (di bawah header) ══════════ --}}
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

                        {{-- File Proposal --}}
                        <x-input
                            type="file"
                            wire:model="form.file"
                            label="File Proposal"
                            required
                            accept=".pdf,.doc,.docx"
                            hint="Maks 10 MB · PDF, DOC, DOCX"
                            :class="$errors->has('form.file')
                                ? '[&_input]:!border-rose-400 dark:[&_input]:!border-rose-500 [&_input]:!ring-2 [&_input]:!ring-rose-500/25'
                                : ''" />

                        {{-- Judul --}}
                        <x-input
                            wire:model="form.title"
                            label="Judul"
                            required
                            placeholder="Judul proposal"
                            :class="$errors->has('form.title')
                                ? '[&_input]:!border-rose-400 dark:[&_input]:!border-rose-500 [&_input]:!ring-2 [&_input]:!ring-rose-500/25'
                                : ''" />

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                            {{-- Skema --}}
                            <x-select
                                wire:model.live="form.research_scheme_id"
                                label="Skema"
                                required
                                size="lg"
                                color="emerald"
                                :class="$errors->has('form.research_scheme_id')
                                    ? '[&_select]:!border-rose-400 dark:[&_select]:!border-rose-500 [&_select]:!ring-2 [&_select]:!ring-rose-500/25'
                                    : ''">
                                <option value="">— Pilih Skema —</option>
                                @foreach ($schemes as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->code }})</option>
                                @endforeach
                            </x-select>

                            {{-- Periode (custom) --}}
                            <div>
                                <label class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                                    Periode
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
                                            Tidak ada periode aktif
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
                            label="Ringkasan"
                            required
                            rows="4"
                            color="emerald"
                            placeholder="Ringkasan proposal..."
                            :class="$errors->has('form.summary')
                                ? '[&_textarea]:!border-rose-400 dark:[&_textarea]:!border-rose-500 [&_textarea]:!ring-2 [&_textarea]:!ring-rose-500/25'
                                : ''" />
                    </div>
                @endif

                @if ($step === 2)
                    <div class="space-y-4">

                        {{-- Budget summary card --}}
                        <div class="rounded-2xl border border-slate-200 dark:border-zinc-700
                                    bg-slate-50 dark:bg-zinc-800/40 p-3">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[11px] font-medium text-slate-700 dark:text-zinc-300">Total
                                    Anggaran</span>
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

                            <flux:button type="button" wire:click="addBudgetItem" icon="plus" variant="primary"
                                class="rounded-full">
                                Add
                            </flux:button>
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
                           shadow-sm shadow-zinc-200/40 hover:shadow-sm hover:shadow-rose-500/15
                           hover:scale-[1.02] active:scale-[0.97]
                           transition-all duration-150">
                    Cancel
                </button>

                <div class="flex flex-col-reverse sm:flex-row gap-1.5">
                    @if ($step === 2)
                        <flux:button type="button" wire:click="prevStep">
                            Previous
                        </flux:button>
                    @endif

                    @if ($step === 1)
                        <flux:button type="button" wire:click="nextStep" variant="primary"
                            wire:loading.attr="disabled" wire:target="nextStep">
                            Next Step
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
                            <span wire:loading.remove wire:target="finish">Simpan Proposal</span>
                            <span wire:loading wire:target="finish">Menyimpan...</span>
                        </button>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>
