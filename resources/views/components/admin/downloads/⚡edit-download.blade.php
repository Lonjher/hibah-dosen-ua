<?php

use App\Models\Download;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public bool $show = false;
    public ?int $downloadId = null;

    public string $title = '';
    public string $description = '';
    public string $category = 'general';
    public $file = null;
    public bool $is_active = true;
    public bool $show_on_welcome = true;
    public bool $show_on_dashboard = true;
    public int $sort_order = 0;

    public ?string $existingFileName = null;
    public ?string $existingFileSize = null;

    protected function rules(): array
    {
        return [
            'title'             => ['required', 'string', 'max:180'],
            'description'       => ['nullable', 'string', 'max:1000'],
            'category'          => ['required', 'in:guideline,template,form,general'],
            'file'              => ['nullable', 'file', 'max:20480',
                                     'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,txt'],
            'is_active'         => ['boolean'],
            'show_on_welcome'   => ['boolean'],
            'show_on_dashboard' => ['boolean'],
            'sort_order'        => ['integer', 'min:0'],
        ];
    }

    protected function messages(): array
    {
        return [
            'file.mimes' => 'File format must be: pdf, doc, docx, xls, xlsx, ppt, pptx, zip, rar, or txt.',
            'file.max'   => 'Maximum file size is 20 MB.',
        ];
    }

    #[On('open-edit-download')]
    public function open(int $id): void
    {
        $dl = Download::find($id);

        if (! $dl) {
            Flux::toast('File not found.', variant: 'danger');
            return;
        }

        $this->resetValidation();
        $this->reset('file');

        $this->downloadId        = $dl->id;
        $this->title             = $dl->title;
        $this->description       = $dl->description ?? '';
        $this->category          = $dl->category;
        $this->is_active         = $dl->is_active;
        $this->show_on_welcome   = $dl->show_on_welcome;
        $this->show_on_dashboard = $dl->show_on_dashboard;
        $this->sort_order        = $dl->sort_order;
        $this->existingFileName  = $dl->file_name;
        $this->existingFileSize  = $dl->file_size_human;
        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
        $this->resetValidation();
        $this->reset('file');
    }

    public function update(): void
    {
        if (! $this->downloadId) return;

        $dl = Download::find($this->downloadId);
        if (! $dl) {
            Flux::toast('File not found.', variant: 'danger');
            $this->close();
            return;
        }

        $data = $this->validate();

        try {
            $payload = [
                'title'             => $data['title'],
                'description'       => $data['description'],
                'category'          => $data['category'],
                'is_active'         => $data['is_active'],
                'show_on_welcome'   => $data['show_on_welcome'],
                'show_on_dashboard' => $data['show_on_dashboard'],
                'sort_order'        => $data['sort_order'],
            ];

            // Replace file if new upload provided
            if ($this->file) {
                if ($dl->file_path && \Storage::disk('public')->exists($dl->file_path)) {
                    \Storage::disk('public')->delete($dl->file_path);
                }

                $payload['file_path'] = $this->file->store('downloads', 'public');
                $payload['file_name'] = $this->file->getClientOriginalName();
                $payload['file_size'] = $this->file->getSize();
                $payload['mime_type'] = $this->file->getMimeType();
            }

            $dl->update($payload);

            Flux::toast('File updated successfully.', variant: 'success');
            $this->dispatch('download-updated');
            $this->close();
        } catch (\Throwable $e) {
            report($e);
            Flux::toast('Failed to update file.', variant: 'danger');
        }
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
        <div class="shrink-0 bg-gradient-to-r from-fuchsia-600 to-violet-500 px-4 py-3">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-7 h-7 rounded-md bg-white/20 flex items-center justify-center shrink-0">
                        <flux:icon.pencil-square class="size-3.5 text-white" />
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-[13px] font-semibold text-white leading-tight">
                            Edit File
                        </h3>
                        <p class="text-[10.5px] text-white/75 mt-0.5 leading-tight">
                            Update metadata or replace the file
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
                           focus:ring-1 focus:ring-fuchsia-500 focus:border-fuchsia-500
                           py-1.5 px-2.5 transition-colors" />
                @error('title')
                    <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Description --}}
            <div>
                <label class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                    Description
                </label>
                <textarea wire:model="description" rows="2"
                    class="block w-full rounded-md shadow-sm text-[11.5px]
                           border-slate-300 dark:border-zinc-600
                           bg-white dark:bg-zinc-800
                           text-slate-900 dark:text-zinc-100
                           focus:ring-1 focus:ring-fuchsia-500 focus:border-fuchsia-500
                           py-1.5 px-2.5 resize-none transition-colors"></textarea>
                @error('description')
                    <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Current file info --}}
            @if ($existingFileName)
                <div class="rounded-md border border-slate-200 dark:border-zinc-700/70
                            bg-slate-50 dark:bg-zinc-800/40
                            px-2.5 py-2">
                    <div class="flex items-center gap-1.5">
                        <flux:icon.document class="size-3 text-slate-400 dark:text-zinc-500 shrink-0" />
                        <p class="text-[9.5px] uppercase font-semibold tracking-wider
                                  text-slate-500 dark:text-zinc-400">
                            Current File
                        </p>
                    </div>
                    <p class="mt-1 text-[11px] font-medium leading-tight truncate
                              text-slate-800 dark:text-zinc-200">
                        {{ $existingFileName }}
                    </p>
                    <p class="text-[9.5px] text-slate-500 dark:text-zinc-500">
                        {{ $existingFileSize }}
                    </p>
                </div>
            @endif

            {{-- Replace File --}}
            <div>
                <label class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                    Replace File (Optional)
                </label>
                <input type="file" wire:model="file"
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.txt"
                    class="block w-full text-[11px]
                           file:mr-2.5 file:py-1 file:px-2.5 file:rounded-md
                           file:border-0 file:text-[10.5px] file:font-medium
                           file:bg-fuchsia-100 file:text-fuchsia-700
                           dark:file:bg-fuchsia-900/40 dark:file:text-fuchsia-300
                           hover:file:bg-fuchsia-200 dark:hover:file:bg-fuchsia-900/60
                           border border-slate-300 dark:border-zinc-600 rounded-md
                           bg-white dark:bg-zinc-800
                           transition-colors" />
                <div class="mt-0.5 flex items-center justify-between">
                    <p class="text-[9.5px] text-slate-400 dark:text-zinc-500">
                        Leave empty to keep the current file
                    </p>
                    <div wire:loading wire:target="file"
                        class="inline-flex items-center gap-1 text-[9.5px] text-fuchsia-600 dark:text-fuchsia-400">
                        <svg class="animate-spin size-2.5" viewBox="0 0 24 24" fill="none"
                             xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/>
                            <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                        </svg>
                        Uploading...
                    </div>
                </div>
                @error('file')
                    <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Category + Sort Order --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">

                {{-- Category --}}
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                        Category
                    </label>
                    <select wire:model="category"
                        class="block w-full rounded-md shadow-sm text-[11.5px]
                               border-slate-300 dark:border-zinc-600
                               bg-white dark:bg-zinc-800
                               text-slate-900 dark:text-zinc-100
                               focus:ring-1 focus:ring-fuchsia-500 focus:border-fuchsia-500
                               py-1.5 pl-2.5 pr-6 transition-colors">
                        <option value="guideline">Guideline</option>
                        <option value="template">Template</option>
                        <option value="form">Form</option>
                        <option value="general">General</option>
                    </select>
                    @error('category')
                        <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Sort Order --}}
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                        Sort Order
                    </label>
                    <input type="number" wire:model="sort_order" min="0"
                        class="block w-full rounded-md shadow-sm text-[11.5px]
                               border-slate-300 dark:border-zinc-600
                               bg-white dark:bg-zinc-800
                               text-slate-900 dark:text-zinc-100
                               focus:ring-1 focus:ring-fuchsia-500 focus:border-fuchsia-500
                               py-1.5 px-2.5 transition-colors" />
                    <p class="mt-0.5 text-[9.5px] text-slate-400 dark:text-zinc-500">
                        Lower = shown first
                    </p>
                </div>
            </div>

            {{-- Toggles --}}
            <div class="space-y-1.5">
                <label class="flex items-center gap-2 cursor-pointer select-none
                              p-2 rounded-md
                              bg-slate-50 dark:bg-zinc-800/40
                              border border-slate-200 dark:border-zinc-700/60
                              hover:bg-slate-100 dark:hover:bg-zinc-800/70
                              transition-colors">
                    <input type="checkbox" wire:model="is_active"
                        class="rounded border-slate-300 dark:border-zinc-600
                               text-fuchsia-600 focus:ring-fuchsia-500 focus:ring-1
                               w-3.5 h-3.5" />
                    <span class="text-[11px] text-slate-700 dark:text-zinc-300">
                        Active (available for download)
                    </span>
                </label>

                <label class="flex items-center gap-2 cursor-pointer select-none
                              p-2 rounded-md
                              bg-slate-50 dark:bg-zinc-800/40
                              border border-slate-200 dark:border-zinc-700/60
                              hover:bg-slate-100 dark:hover:bg-zinc-800/70
                              transition-colors">
                    <input type="checkbox" wire:model="show_on_welcome"
                        class="rounded border-slate-300 dark:border-zinc-600
                               text-fuchsia-600 focus:ring-fuchsia-500 focus:ring-1
                               w-3.5 h-3.5" />
                    <span class="text-[11px] text-slate-700 dark:text-zinc-300">
                        Show on Welcome Page
                    </span>
                </label>

                <label class="flex items-center gap-2 cursor-pointer select-none
                              p-2 rounded-md
                              bg-slate-50 dark:bg-zinc-800/40
                              border border-slate-200 dark:border-zinc-700/60
                              hover:bg-slate-100 dark:hover:bg-zinc-800/70
                              transition-colors">
                    <input type="checkbox" wire:model="show_on_dashboard"
                        class="rounded border-slate-300 dark:border-zinc-600
                               text-fuchsia-600 focus:ring-fuchsia-500 focus:ring-1
                               w-3.5 h-3.5" />
                    <span class="text-[11px] text-slate-700 dark:text-zinc-300">
                        Show on User Dashboard
                    </span>
                </label>
            </div>
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
                wire:loading.attr="disabled" wire:target="update,file"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5
                       px-3 py-1.5 text-[11px] font-medium rounded-md
                       text-white bg-fuchsia-600 hover:bg-fuchsia-700
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
