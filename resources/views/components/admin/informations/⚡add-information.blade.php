<?php

use App\Models\Informations;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public bool $show = false;
    public string $title = '';
    public string $content = '';
    public string $type = 'info';
    public bool $is_published = true;
    public ?string $published_at = null;
    public ?string $expires_at = null;
    public int $priority = 0;

    protected function rules(): array
    {
        return [
            'title'        => ['required', 'string', 'max:180'],
            'content'      => ['required', 'string', 'max:5000'],
            'type'         => ['required', 'in:info,success,warning,danger'],
            'is_published' => ['boolean'],
            'published_at' => ['nullable', 'date'],
            'expires_at'   => ['nullable', 'date', 'after:published_at'],
            'priority'     => ['integer', 'min:0', 'max:99'],
        ];
    }

    protected function messages(): array
    {
        return [
            'expires_at.after' => 'The expiry date must be after the publish date.',
        ];
    }

    #[On('open-add-information')]
    public function open(): void
    {
        $this->resetValidation();
        $this->reset(['title', 'content', 'type', 'published_at', 'expires_at', 'priority']);
        $this->type = 'info';
        $this->is_published = true;
        $this->published_at = now()->format('Y-m-d\TH:i');
        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
        $this->resetValidation();
    }

    public function save(): void
    {
        $data = $this->validate();

        Informations::create([
            'user_id'      => auth()->id(),
            'title'        => $data['title'],
            'content'      => $data['content'],
            'type'         => $data['type'],
            'is_published' => $data['is_published'],
            'published_at' => $data['published_at'] ?: now(),
            'expires_at'   => $data['expires_at'] ?: null,
            'priority'     => $data['priority'],
        ]);

        Flux::toast('Information added successfully.', variant: 'success');
        $this->dispatch('information-added');
        $this->close();
    }
};
?>

<div x-data x-show="$wire.show" x-transition.opacity x-cloak
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4"
    @click.self="$wire.close()">

    <div class="flex max-h-[92vh] w-full sm:max-w-md flex-col overflow-hidden
                bg-white dark:bg-zinc-900 rounded-t-2xl sm:rounded-xl shadow-2xl
                border border-slate-200 dark:border-zinc-700"
        @click.stop>

        {{-- ══════════ HEADER ══════════ --}}
        <div class="shrink-0 bg-gradient-to-r from-sky-600 to-sky-500 px-4 py-3">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-7 h-7 rounded-md bg-white/20 flex items-center justify-center shrink-0">
                        <flux:icon.megaphone class="size-3.5 text-white" />
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-[13px] font-semibold text-white leading-tight">
                            Add Information
                        </h3>
                        <p class="text-[10.5px] text-white/75 mt-0.5 leading-tight">
                            Will appear on user dashboard
                        </p>
                    </div>
                </div>
                <button type="button" wire:click="close"
                    aria-label="Close"
                    class="shrink-0 w-6 h-6 rounded-md flex items-center justify-center
                           text-white/80 hover:text-white hover:bg-white/10
                           transition-colors">
                    <flux:icon.x-mark class="size-3.5" />
                </button>
            </div>
        </div>

        {{-- ══════════ BODY ══════════ --}}
        <div class="flex-1 overflow-y-auto px-4 py-4 space-y-3">

            {{-- Title --}}
            <div>
                <label class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                    Title <span class="text-rose-500">*</span>
                </label>
                <input type="text" wire:model="title"
                    placeholder="e.g. Proposal Submission Deadline"
                    class="block w-full rounded-md shadow-sm text-[11.5px]
                           border-slate-300 dark:border-zinc-600
                           bg-white dark:bg-zinc-800
                           text-slate-900 dark:text-zinc-100
                           placeholder:text-slate-400 dark:placeholder:text-zinc-500
                           focus:ring-1 focus:ring-sky-500 focus:border-sky-500
                           py-1.5 px-2.5 transition-colors" />
                @error('title')
                    <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Content --}}
            <div>
                <label class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                    Content <span class="text-rose-500">*</span>
                </label>
                <textarea wire:model="content" rows="4"
                    placeholder="Write the information..."
                    class="block w-full rounded-md shadow-sm text-[11.5px]
                           border-slate-300 dark:border-zinc-600
                           bg-white dark:bg-zinc-800
                           text-slate-900 dark:text-zinc-100
                           placeholder:text-slate-400 dark:placeholder:text-zinc-500
                           focus:ring-1 focus:ring-sky-500 focus:border-sky-500
                           py-1.5 px-2.5 resize-none transition-colors"></textarea>
                @error('content')
                    <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Type + Priority --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">

                {{-- Type --}}
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                        Type
                    </label>
                    <select wire:model="type"
                        class="block w-full rounded-md shadow-sm text-[11.5px]
                               border-slate-300 dark:border-zinc-600
                               bg-white dark:bg-zinc-800
                               text-slate-900 dark:text-zinc-100
                               focus:ring-1 focus:ring-sky-500 focus:border-sky-500
                               py-1.5 pl-2.5 pr-6 transition-colors">
                        <option value="info">Info</option>
                        <option value="success">Success</option>
                        <option value="warning">Warning</option>
                        <option value="danger">Important</option>
                    </select>
                    @error('type')
                        <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Priority --}}
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                        Priority
                    </label>
                    <input type="number" wire:model="priority" min="0" max="99"
                        class="block w-full rounded-md shadow-sm text-[11.5px]
                               border-slate-300 dark:border-zinc-600
                               bg-white dark:bg-zinc-800
                               text-slate-900 dark:text-zinc-100
                               focus:ring-1 focus:ring-sky-500 focus:border-sky-500
                               py-1.5 px-2.5 transition-colors" />
                    <p class="mt-0.5 text-[9.5px] text-slate-400 dark:text-zinc-500">
                        Higher = displayed first
                    </p>
                </div>
            </div>

            {{-- Publish At + Expires At --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">

                {{-- Publish At --}}
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                        Publish At
                    </label>
                    <input type="datetime-local" wire:model="published_at"
                        class="block w-full rounded-md shadow-sm text-[11.5px]
                               border-slate-300 dark:border-zinc-600
                               bg-white dark:bg-zinc-800
                               text-slate-900 dark:text-zinc-100
                               focus:ring-1 focus:ring-sky-500 focus:border-sky-500
                               py-1.5 px-2.5 transition-colors" />
                    @error('published_at')
                        <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Expires At --}}
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                        Expires At
                    </label>
                    <input type="datetime-local" wire:model="expires_at"
                        class="block w-full rounded-md shadow-sm text-[11.5px]
                               border-slate-300 dark:border-zinc-600
                               bg-white dark:bg-zinc-800
                               text-slate-900 dark:text-zinc-100
                               focus:ring-1 focus:ring-sky-500 focus:border-sky-500
                               py-1.5 px-2.5 transition-colors" />
                    <p class="mt-0.5 text-[9.5px] text-slate-400 dark:text-zinc-500">
                        Leave empty for no expiry
                    </p>
                    @error('expires_at')
                        <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Publish toggle --}}
            <label class="flex items-center gap-2 cursor-pointer select-none
                          p-2 rounded-md
                          bg-slate-50 dark:bg-zinc-800/40
                          border border-slate-200 dark:border-zinc-700/60
                          hover:bg-slate-100 dark:hover:bg-zinc-800/70
                          transition-colors">
                <input type="checkbox" wire:model="is_published"
                    class="rounded border-slate-300 dark:border-zinc-600
                           text-sky-600 focus:ring-sky-500 focus:ring-1
                           w-3.5 h-3.5" />
                <span class="text-[11px] text-slate-700 dark:text-zinc-300">
                    Publish immediately
                </span>
            </label>
        </div>

        {{-- ══════════ FOOTER ══════════ --}}
        <div class="shrink-0 flex flex-col-reverse sm:flex-row sm:justify-end gap-1.5
                    px-4 py-3 border-t border-slate-200 dark:border-zinc-700
                    bg-slate-50/50 dark:bg-zinc-900/50">
            <button type="button" wire:click="close"
                class="w-full sm:w-auto px-3 py-1.5 text-[11px] font-medium
                       rounded-md
                       text-slate-700 dark:text-zinc-300
                       bg-white dark:bg-zinc-800
                       border border-slate-300 dark:border-zinc-600
                       hover:bg-slate-50 dark:hover:bg-zinc-700
                       transition-colors">
                Cancel
            </button>
            <button type="button" wire:click="save"
                wire:loading.attr="disabled" wire:target="save"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5
                       px-3 py-1.5 text-[11px] font-medium rounded-md
                       text-white bg-sky-600 hover:bg-sky-700
                       disabled:opacity-60 disabled:cursor-wait
                       transition-colors">
                <svg wire:loading wire:target="save"
                     class="animate-spin size-3" viewBox="0 0 24 24" fill="none"
                     xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/>
                    <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                </svg>
                <span wire:loading.remove wire:target="save">Save Information</span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>
        </div>
    </div>
</div>
