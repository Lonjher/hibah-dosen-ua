<?php

use App\Models\Informations;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public bool $show = false;
    public ?int $informationId = null;

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

    #[On('open-edit-information')]
    public function open(int $id): void
    {
        $info = Informations::find($id);

        if (! $info) {
            Flux::toast('Information not found.', variant: 'danger');
            return;
        }

        $this->resetValidation();
        $this->informationId = $info->id;
        $this->title         = $info->title;
        $this->content       = $info->content;
        $this->type          = $info->type;
        $this->is_published  = $info->is_published;
        $this->published_at  = $info->published_at?->format('Y-m-d\TH:i');
        $this->expires_at    = $info->expires_at?->format('Y-m-d\TH:i');
        $this->priority      = $info->priority;
        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
        $this->resetValidation();
    }

    public function update(): void
    {
        if (! $this->informationId) return;

        $info = Informations::find($this->informationId);
        if (! $info) {
            Flux::toast('Information not found.', variant: 'danger');
            $this->close();
            return;
        }

        $data = $this->validate();

        $info->update([
            'title'        => $data['title'],
            'content'      => $data['content'],
            'type'         => $data['type'],
            'is_published' => $data['is_published'],
            'published_at' => $data['published_at'] ?: $info->published_at,
            'expires_at'   => $data['expires_at'] ?: null,
            'priority'     => $data['priority'],
        ]);

        Flux::toast('Information updated successfully.', variant: 'success');
        $this->dispatch('information-updated');
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
        <div class="shrink-0 bg-gradient-to-r from-violet-600 to-violet-500 px-4 py-3">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-7 h-7 rounded-md bg-white/20 flex items-center justify-center shrink-0">
                        <flux:icon.pencil-square class="size-3.5 text-white" />
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-[13px] font-semibold text-white leading-tight">
                            Edit Information
                        </h3>
                        <p class="text-[10.5px] text-white/75 mt-0.5 leading-tight">
                            Update the existing information
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
                    class="block w-full rounded-md shadow-sm text-[11.5px]
                           border-slate-300 dark:border-zinc-600
                           bg-white dark:bg-zinc-800
                           text-slate-900 dark:text-zinc-100
                           focus:ring-1 focus:ring-violet-500 focus:border-violet-500
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
                    class="block w-full rounded-md shadow-sm text-[11.5px]
                           border-slate-300 dark:border-zinc-600
                           bg-white dark:bg-zinc-800
                           text-slate-900 dark:text-zinc-100
                           focus:ring-1 focus:ring-violet-500 focus:border-violet-500
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
                               focus:ring-1 focus:ring-violet-500 focus:border-violet-500
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
                               focus:ring-1 focus:ring-violet-500 focus:border-violet-500
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
                               focus:ring-1 focus:ring-violet-500 focus:border-violet-500
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
                               focus:ring-1 focus:ring-violet-500 focus:border-violet-500
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
                           text-violet-600 focus:ring-violet-500 focus:ring-1
                           w-3.5 h-3.5" />
                <span class="text-[11px] text-slate-700 dark:text-zinc-300">
                    Published
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
            <button type="button" wire:click="update"
                wire:loading.attr="disabled" wire:target="update"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5
                       px-3 py-1.5 text-[11px] font-medium rounded-md
                       text-white bg-violet-600 hover:bg-violet-700
                       disabled:opacity-60 disabled:cursor-wait
                       transition-colors">
                <svg wire:loading wire:target="update"
                     class="animate-spin size-3" viewBox="0 0 24 24" fill="none"
                     xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/>
                    <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                </svg>
                <span wire:loading.remove wire:target="update">Save Changes</span>
                <span wire:loading wire:target="update">Saving...</span>
            </button>
        </div>
    </div>
</div>
