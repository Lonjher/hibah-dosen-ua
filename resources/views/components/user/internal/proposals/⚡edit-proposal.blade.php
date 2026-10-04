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
        $proposal = Proposal::where('user_id', auth()->id())->findOrFail($id);

        if (! in_array($proposal->status, ['pending', 'revised'])) {
            Flux::toast('Proposal ini tidak dapat diedit.', variant: 'danger');
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

        $this->form->proposal->update([
            'research_scheme_id' => $this->form->research_scheme_id,
            'title'              => $this->form->title,
            'summary'            => $this->form->summary,
            'keywords'           => $this->form->keywords,
            'period_id'          => $this->form->period_id,
        ]);

        $this->step = 2;
    }

    public function prevStep(): void
    {
        $this->step = 1;
    }

    public function addBudgetItem(): void
    {
        if (! $this->form->proposal) return;

        $this->budgetForm->proposal_id = $this->form->proposal->id;
        $this->budgetForm->validate();

        $amount  = (int) preg_replace('/\D/', '', (string) $this->budgetForm->amount);
        $proposal = $this->form->proposal->load('researchScheme');
        $limit    = (int) ($proposal->researchScheme?->budget_limit ?? 0);
        $current  = (int) BudgetProposal::where('proposal_id', $proposal->id)->sum('amount');

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
        if (! $this->form->proposal) return;
        BudgetProposal::where('proposal_id', $this->form->proposal->id)->where('id', $id)->delete();
        Flux::toast('Item anggaran dihapus.', variant: 'success');
    }

    public function save(): void
    {
        if (! $this->form->proposal) return;

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
            $proposal    = $this->form->proposal->load('researchScheme');
            $budgetItems = BudgetProposal::where('proposal_id', $proposal->id)->orderBy('id')->get();
            $budgetTotal = (int) $budgetItems->sum('amount');
            $budgetLimit = (int) ($proposal->researchScheme?->budget_limit ?? 0);
        } elseif ($this->form->research_scheme_id) {
            $budgetLimit = (int) (ResearchScheme::find($this->form->research_scheme_id)?->budget_limit ?? 0);
        }

        return [
            'schemes'         => ResearchScheme::where('is_active', true)->orderBy('name')->get(),
            'periods'         => Period::orderByDesc('periode')->get(),
            'budgetItems'     => $budgetItems,
            'budgetTotal'     => $budgetTotal,
            'budgetLimit'     => $budgetLimit,
            'budgetRemaining' => $budgetLimit - $budgetTotal,
            'budgetPercent'   => $budgetLimit > 0 ? min(round(($budgetTotal / $budgetLimit) * 100, 1), 100) : 0,
        ];
    }
};
?>

<div
    x-data="{
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
    }"
    x-show="show" x-transition.opacity x-cloak
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4"
    @click.self="show = false">

    <div class="flex max-h-[90vh] w-full sm:max-w-2xl flex-col overflow-hidden
                bg-white dark:bg-zinc-900 rounded-t-2xl sm:rounded-xl shadow-2xl
                border border-slate-200 dark:border-zinc-700"
        @click.stop>

        <form class="flex min-h-0 flex-1 flex-col">

            {{-- HEADER blue --}}
            <div class="shrink-0 bg-gradient-to-r from-blue-600 to-blue-500
                        px-5 sm:px-6 py-4 rounded-t-2xl sm:rounded-t-xl">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                        <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center shrink-0">
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
                        class="shrink-0 w-7 h-7 rounded-lg flex items-center justify-center
                               text-white/80 hover:text-white hover:bg-white/10 transition-colors">
                        <flux:icon.x-mark class="size-4" />
                    </button>
                </div>
            </div>

            {{-- BODY --}}
            <div class="flex-1 overflow-y-auto px-5 sm:px-6 py-5 space-y-4">

                <template x-if="errorMessage">
                    <div class="flex items-start gap-2 p-3 rounded-lg
                                bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800">
                        <flux:icon.exclamation-triangle class="size-4 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5" />
                        <p class="text-[11px] text-rose-700 dark:text-rose-300" x-text="errorMessage"></p>
                    </div>
                </template>

                @if ($step === 1)
                    <div class="space-y-4">

                        {{-- Judul --}}
                        <div>
                            <x-input
                                wire:model="form.title"
                                label="Judul"
                                required />
                            @error('form.title')
                                <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                            {{-- Skema --}}
                            <div>
                                <x-select
                                    wire:model.live="form.research_scheme_id"
                                    label="Skema"
                                    required
                                    size="lg"
                                    color="blue">
                                    @foreach ($schemes as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->code }})</option>
                                    @endforeach
                                </x-select>
                                @error('form.research_scheme_id')
                                    <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Periode --}}
                            <div>
                                <x-select
                                    wire:model="form.period_id"
                                    label="Periode"
                                    required
                                    size="lg"
                                    color="blue">
                                    @foreach ($periods as $p)
                                        <option value="{{ $p->id }}">{{ $p->periode }}</option>
                                    @endforeach
                                </x-select>
                                @error('form.period_id')
                                    <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Kata Kunci --}}
                        <div>
                            <x-input
                                wire:model="form.keywords"
                                label="Kata Kunci"
                                required />
                            @error('form.keywords')
                                <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Ringkasan --}}
                        <div>
                            <x-textarea
                                wire:model="form.summary"
                                label="Ringkasan"
                                required
                                rows="4"
                                color="blue" />
                            @error('form.summary')
                                <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                @endif

                @if ($step === 2)
                    <div class="space-y-4">

                        {{-- Budget summary card --}}
                        <div class="rounded-xl border border-slate-200 dark:border-zinc-700
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
                            <div>
                                <x-input
                                    wire:model="budgetForm.item_name"
                                    label="Nama Item" />
                                @error('budgetForm.item_name')
                                    <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <x-input
                                    wire:model="budgetForm.amount"
                                    label="Jumlah (Rp)" />
                                @error('budgetForm.amount')
                                    <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <flux:button type="button" wire:click="addBudgetItem"
                                icon="plus" variant="primary">
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
                                               transition-colors">
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
                        bg-white dark:bg-zinc-900 rounded-b-2xl sm:rounded-b-xl">
                <flux:button type="button" @click="show = false" variant="danger">Cancel</flux:button>
                <div class="flex flex-col-reverse sm:flex-row gap-2">
                    @if ($step === 2)
                        <flux:button type="button" wire:click="prevStep">Previous</flux:button>
                    @endif
                    @if ($step === 1)
                        <flux:button type="button" wire:click="nextStep" variant="primary">Next Step</flux:button>
                    @else
                        <flux:button type="button" wire:click="save" variant="primary"
                            wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save">Update Proposal</span>
                            <span wire:loading.flex wire:target="save">Menyimpan...</span>
                        </flux:button>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>
