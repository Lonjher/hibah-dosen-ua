<?php

namespace App\Livewire\Forms;

use App\Models\Period;
use Livewire\Form;

class PeriodForm extends Form
{
    public ?Period $period = null;

    public string $periode = '';
    public ?string $open_from = null;
    public ?string $open_to = null;
    public bool $is_active = false;

    // ═══════════════ Validation ═══════════════

    public function rules(): array
    {
        return [
            'periode'   => ['required', 'string', 'max:255'],
            'open_from' => ['required', 'date'],
            'open_to'   => ['required', 'date', 'after_or_equal:open_from'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'periode.required'       => 'Period name is required.',
            'periode.string'         => 'Period name must be a text.',
            'periode.max'            => 'Period name must not exceed 255 characters.',

            'open_from.required'     => 'Start date is required.',
            'open_from.date'         => 'Start date must be a valid date.',

            'open_to.required'       => 'End date is required.',
            'open_to.date'           => 'End date must be a valid date.',
            'open_to.after_or_equal' => 'End date must be the same as or later than the start date.',

            'is_active.boolean'      => 'Active status must be true or false.',
        ];
    }

    // ═══════════════ Load ═══════════════

    public function setPeriod(Period $period): void
    {
        $this->period    = $period;
        $this->periode   = $period->periode;
        $this->open_from = $period->open_from?->format('Y-m-d');
        $this->open_to   = $period->open_to?->format('Y-m-d');
        $this->is_active = (bool) $period->is_active;
    }

    // ═══════════════ Actions ═══════════════

    public function create(): Period
    {
        $this->validate();

        // Guard: if is_active = true, deactivate all other periods
        if ($this->is_active) {
            Period::where('is_active', true)
                ->update(['is_active' => false]);
        }

        $period = Period::create([
            'periode'   => $this->periode,
            'open_from' => $this->open_from,
            'open_to'   => $this->open_to,
            'is_active' => $this->is_active,
        ]);

        $this->reset();

        return $period;
    }

    public function update(): Period
    {
        if (! $this->period) {
            throw new \RuntimeException('No period loaded.');
        }

        $this->validate();

        // Guard: if is_active = true, deactivate all other periods except itself
        if ($this->is_active) {
            Period::where('is_active', true)
                ->where('id', '!=', $this->period->id)
                ->update(['is_active' => false]);
        }

        $this->period->update([
            'periode'   => $this->periode,
            'open_from' => $this->open_from,
            'open_to'   => $this->open_to,
            'is_active' => $this->is_active,
        ]);

        $updated = $this->period;

        $this->reset();

        return $updated;
    }
}
