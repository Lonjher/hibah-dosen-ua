<?php

namespace App\Livewire\Forms;

use App\Models\Period;
use Livewire\Form;

class PeriodForm extends Form
{
    public ?Period $period = null;

    public string  $periode = '';
    public ?string $open_from = null;
    public ?string $open_to = null;
    public bool    $is_active = false;

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
            'periode.string'         => 'Period name must be a string.',
            'periode.max'            => 'Period name must not exceed 255 characters.',

            'open_from.required'     => 'Open From is required.',
            'open_from.date'         => 'Open From must be a valid date.',

            'open_to.required'       => 'Open To is required.',
            'open_to.date'           => 'Open To must be a valid date.',
            'open_to.after_or_equal' => 'Open To must be the same as or after Open From.',

            'is_active.boolean'      => 'Active must be true or false.',
        ];
    }

    // ═══════════════ Load ═══════════════

    /**
     * Load period dari database ke form (untuk mode edit).
     */
    public function setPeriod(Period $period): void
    {
        $this->period    = $period;
        $this->periode   = $period->periode;
        $this->open_from = $period->open_from?->format('Y-m-d\TH:i');
        $this->open_to   = $period->open_to?->format('Y-m-d\TH:i');
        $this->is_active = (bool) $period->is_active;
    }

    // ═══════════════ Actions ═══════════════

    /**
     * Simpan sebagai period baru.
     */
    public function create(): Period
    {
        $this->validate();

        $period = Period::create([
            'periode'   => $this->periode,
            'open_from' => $this->open_from,
            'open_to'   => $this->open_to,
            'is_active' => $this->is_active,
        ]);

        $this->reset();

        return $period;
    }

    /**
     * Update period yang sedang di-edit.
     *
     * @throws \RuntimeException jika tidak ada period yang di-load.
     */
    public function update(): Period
    {
        if (! $this->period) {
            throw new \RuntimeException(
                'No period loaded. Call setPeriod() before update().'
            );
        }

        $this->validate();

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
