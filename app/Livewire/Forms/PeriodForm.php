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
            'periode.required'       => 'Nama periode wajib diisi.',
            'periode.string'         => 'Nama periode harus berupa teks.',
            'periode.max'            => 'Nama periode maksimal 255 karakter.',

            'open_from.required'     => 'Tanggal buka wajib diisi.',
            'open_from.date'         => 'Tanggal buka harus tanggal valid.',

            'open_to.required'       => 'Tanggal tutup wajib diisi.',
            'open_to.date'           => 'Tanggal tutup harus tanggal valid.',
            'open_to.after_or_equal' => 'Tanggal tutup harus sama atau setelah tanggal buka.',

            'is_active.boolean'      => 'Status aktif harus true atau false.',
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
