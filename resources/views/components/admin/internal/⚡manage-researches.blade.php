<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="p-4 sm:p-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <x-dashboard-header icon="beaker" title="Kelola Penelitian" leading="Manajemen penelitian Internal dosen." />
        <flux:button icon="plus" wire:click="create" variant="primary" size="sm" class="shrink-0">Tambah
        </flux:button>
    </div>
    {{-- Order your soul. Reduce your wants. - Augustine --}}
</div>
