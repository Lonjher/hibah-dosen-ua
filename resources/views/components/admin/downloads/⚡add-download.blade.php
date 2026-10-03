<?php

use App\Models\Download;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public bool $show = false;
    public string $title = '';
    public string $description = '';
    public string $category = 'general';
    public $file = null;
    public bool $is_active = true;
    public bool $show_on_welcome = true;
    public bool $show_on_dashboard = true;
    public int $sort_order = 0;

    protected function rules(): array
    {
        return [
            'title'             => ['required', 'string', 'max:180'],
            'description'       => ['nullable', 'string', 'max:1000'],
            'category'          => ['required', 'in:guideline,template,form,general'],
            'file'              => ['required', 'file', 'max:20480',
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

    #[On('open-add-download')]
    public function open(): void
    {
        $this->resetValidation();
        $this->reset(['title', 'description', 'file', 'sort_order']);
        $this->category = 'general';
        $this->is_active = true;
        $this->show_on_welcome = true;
        $this->show_on_dashboard = true;
        $this->sort_order = 0;
        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
        $this->resetValidation();
        $this->reset('file');
    }

    public function save(): void
    {
        $data = $this->validate();

        try {
            $original = $this->file->getClientOriginalName();
            $size     = $this->file->getSize();
            $mime     = $this->file->getMimeType();
            $path     = $this->file->store('downloads', 'public');

            Download::create([
                'user_id'           => auth()->id(),
                'title'             => $data['title'],
                'description'       => $data['description'],
                'category'          => $data['category'],
                'file_path'         => $path,
                'file_name'         => $original,
                'file_size'         => $size,
                'mime_type'         => $mime,
                'is_active'         => $data['is_active'],
                'show_on_welcome'   => $data['show_on_welcome'],
                'show_on_dashboard' => $data['show_on_dashboard'],
                'sort_order'        => $data['sort_order'],
            ]);

            Flux::toast('File uploaded successfully.', variant: 'success');
            $this->dispatch('download-added');
            $this->close();
        } catch (\Throwable $e) {
            report($e);
            Flux::toast('Failed to upload file.', variant: 'danger');
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
        <div class="shrink-0 bg-gradient-to-r from-violet-600 to-violet-500 px-4 py-3">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-7 h-7 rounded-md bg-white/20 flex items-center justify-center shrink-0">
                        <flux:icon.arrow-up-tray class="size-3.5 text-white" />
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-[13px] font-semibold text-white leading-tight">
                            Add File
                        </h3>
                        <p class="text-[10.5px] text-white/75 mt-0.5 leading-tight">
                            Upload a file for users to download
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
                    placeholder="e.g. Proposal Writing Guideline 2026"
                    class="block w-full rounded-md shadow-sm text-[11.5px]
                           border-slate-300 dark:border-zinc-600
                           bg-white dark:bg-zinc-800
                           text-slate-900 dark:text-zinc-100
                           placeholder:text-slate-400 dark:placeholder:text-zinc-500
                           focus:ring-1 focus:ring-violet-500 focus:border-violet-500
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
                    placeholder="Short description..."
                    class="block w-full rounded-md shadow-sm text-[11.5px]
                           border-slate-300 dark:border-zinc-600
                           bg-white dark:bg-zinc-800
                           text-slate-900 dark:text-zinc-100
                           placeholder:text-slate-400 dark:placeholder:text-zinc-500
                           focus:ring-1 focus:ring-violet-500 focus:border-violet-500
                           py-1.5 px-2.5 resize-none transition-colors"></textarea>
                @error('description')
                    <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- File --}}
            <div>
                <label class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                    File <span class="text-rose-500">*</span>
                </label>
                <input type="file" wire:model="file"
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.txt"
                    class="block w-full text-[11px]
                           file:mr-2.5 file:py-1 file:px-2.5 file:rounded-md
                           file:border-0 file:text-[10.5px] file:font-medium
                           file:bg-violet-100 file:text-violet-700
                           dark:file:bg-violet-900/40 dark:file:text-violet-300
                           hover:file:bg-violet-200 dark:hover:file:bg-violet-900/60
                           border border-slate-300 dark:border-zinc-600 rounded-md
                           bg-white dark:bg-zinc-800
                           transition-colors" />
                <div class="mt-0.5 flex items-center justify-between">
                    <p class="text-[9.5px] text-slate-400 dark:text-zinc-500">
                        Max 20 MB · pdf, doc, xls, ppt, zip, rar, txt
                    </p>
                    <div wire:loading wire:target="file"
                        class="inline-flex items-center gap-1 text-[9.5px] text-violet-600 dark:text-violet-400">
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
                               focus:ring-1 focus:ring-violet-500 focus:border-violet-500
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
                               focus:ring-1 focus:ring-violet-500 focus:border-violet-500
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
                               text-violet-600 focus:ring-violet-500 focus:ring-1
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
                               text-violet-600 focus:ring-violet-500 focus:ring-1
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
                               text-violet-600 focus:ring-violet-500 focus:ring-1
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
            <button type="button" wire:click="save"
                wire:loading.attr="disabled" wire:target="save,file"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5
                       px-3 py-1.5 text-[11px] font-medium rounded-md
                       text-white bg-violet-600 hover:bg-violet-700
                       disabled:opacity-60 disabled:cursor-wait
                       transition-colors">
                <svg wire:loading wire:target="save"
                     class="animate-spin size-3" viewBox="0 0 24 24" fill="none"
                     xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/>
                    <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                </svg>
                <span wire:loading.remove wire:target="save">Upload File</span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>
        </div>
    </div>
</div>
