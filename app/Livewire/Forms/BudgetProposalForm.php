<?php

namespace App\Livewire\Forms;

use App\Models\BudgetProposal;
use Livewire\Form;

class BudgetProposalForm extends Form
{
    public ?BudgetProposal $budgetProposal = null;
    public ?int $proposal_id = null;
    public string $item_name = '';
    public ?string $amount = null; // ← changed from ?int to ?string

    // ═══════════════ Validation ═══════════════

    public function rules(): array
    {
        return [
            'proposal_id' => ['nullable', 'exists:proposals,id'],
            'item_name'   => ['required', 'string', 'max:255'],
            'amount'      => ['required', 'numeric', 'min:0', 'max:999999999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'item_name.required' => 'Item name is required.',
            'item_name.max'      => 'Item name must not exceed 255 characters.',
            'amount.required'    => 'Amount is required.',
            'amount.numeric'     => 'Amount must be a number.',
            'amount.min'         => 'Amount cannot be negative.',
            'amount.max'         => 'Amount is too large.',
        ];
    }

    // ═══════════════ Load ═══════════════

    public function setBudgetProposal(BudgetProposal $budgetProposal): void
    {
        $this->budgetProposal = $budgetProposal;
        $this->proposal_id    = $budgetProposal->proposal_id;
        $this->item_name      = $budgetProposal->item_name;
        $this->amount         = (string) $budgetProposal->amount;
    }

    // ═══════════════ Actions ═══════════════

    public function create(): BudgetProposal
    {
        $this->validate();

        // Remove separators (e.g. "1.000.000" → 1000000)
        $amount = (int) preg_replace('/\D/', '', (string) $this->amount);

        $budget = BudgetProposal::create([
            'proposal_id' => $this->proposal_id,
            'item_name'   => $this->item_name,
            'amount'      => $amount,
        ]);

        $this->reset('item_name', 'amount');

        return $budget;
    }

    public function update(): BudgetProposal
    {
        if (! $this->budgetProposal) {
            throw new \RuntimeException(
                'No budget proposal loaded. Call setBudgetProposal() before update().'
            );
        }

        $this->validate();

        $amount = (int) preg_replace('/\D/', '', (string) $this->amount);

        $this->budgetProposal->update([
            'item_name' => $this->item_name,
            'amount'    => $amount,
        ]);

        $updated = $this->budgetProposal;

        $this->reset('item_name', 'amount');

        return $updated;
    }
}
