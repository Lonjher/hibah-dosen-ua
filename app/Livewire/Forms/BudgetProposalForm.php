<?php

namespace App\Livewire\Forms;

use App\Models\BudgetProposal;
use Livewire\Form;

class BudgetProposalForm extends Form
{
    public ?BudgetProposal $budgetProposal = null;

    public ?int    $proposal_id = null;
    public string  $item_name = '';
    public ?string $amount = null;   // ← ubah dari ?int ke ?string

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
            'item_name.required' => 'Nama item wajib diisi.',
            'item_name.max'      => 'Nama item maksimal 255 karakter.',
            'amount.required'    => 'Jumlah wajib diisi.',
            'amount.numeric'     => 'Jumlah harus berupa angka.',
            'amount.min'         => 'Jumlah tidak boleh negatif.',
            'amount.max'         => 'Jumlah terlalu besar.',
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

        // Bersihkan separator (misal "1.000.000" → 1000000)
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
