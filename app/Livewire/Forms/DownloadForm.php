<?php

namespace App\Livewire\Forms;

use App\Models\Download;
use Livewire\Form;
use Livewire\WithFileUploads;

class DownloadForm extends Form
{
    public ?Download $download = null;

    public string $title = '';
    public string $description = '';
    public string $category = 'general';
    public $file = null;

    public bool $is_active = true;
    public bool $show_on_welcome = true;
    public bool $show_on_dashboard = true;
    public int $sort_order = 0;

    /* ============================================================
     |  VALIDATION
     | ============================================================ */

    protected function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category'    => ['required', 'in:guideline,template,form,general'],

            'file' => $this->download
                ? [
                    'nullable',
                    'file',
                    'max:20480',
                    'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,txt',
                ]
                : [
                    'required',
                    'file',
                    'max:20480',
                    'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,txt',
                ],

            'is_active'         => ['boolean'],
            'show_on_welcome'   => ['boolean'],
            'show_on_dashboard' => ['boolean'],
            'sort_order'        => ['integer', 'min:0'],
        ];
    }

    protected function messages(): array
    {
        return [
            'title.required'       => 'Title is required.',
            'title.max'            => 'Title must not exceed 180 characters.',
            'description.max'      => 'Description must not exceed 1000 characters.',
            'category.required'    => 'Category is required.',
            'category.in'          => 'Invalid category.',
            'file.required'       => 'Please select a file to upload.',
            'file.mimes'          => 'File format must be: pdf, doc, docx, xls, xlsx, ppt, pptx, zip, rar, or txt.',
            'file.max'            => 'Maximum file size is 20 MB.',
            'sort_order.integer'  => 'Sort order must be a number.',
            'sort_order.min'      => 'Sort order must be at least 0.',
        ];
    }

    /* ============================================================
     |  SETUP
     | ============================================================ */

    public function setDownload(Download $download): void
    {
        $this->download          = $download;
        $this->title             = $download->title;
        $this->description       = $download->description ?? '';
        $this->category          = $download->category;
        $this->is_active         = (bool) $download->is_active;
        $this->show_on_welcome   = (bool) $download->show_on_welcome;
        $this->show_on_dashboard = (bool) $download->show_on_dashboard;
        $this->sort_order        = (int) $download->sort_order;
    }

    /* ============================================================
     |  PERSIST
     | ============================================================ */

    public function create(): Download
    {
        $original = $this->file->getClientOriginalName();
        $size     = $this->file->getSize();
        $mime     = $this->file->getMimeType();
        $path     = $this->file->store('downloads', 'public');

        return Download::create([
            'user_id'           => auth()->id(),
            'title'             => $this->title,
            'description'       => $this->description,
            'category'          => $this->category,
            'file_path'         => $path,
            'file_name'         => $original,
            'file_size'         => $size,
            'mime_type'         => $mime,
            'is_active'         => $this->is_active,
            'show_on_welcome'   => $this->show_on_welcome,
            'show_on_dashboard' => $this->show_on_dashboard,
            'sort_order'        => $this->sort_order,
        ]);
    }

    public function update(): void
    {
        if (! $this->download) {
            return;
        }

        $payload = [
            'title'             => $this->title,
            'description'       => $this->description,
            'category'          => $this->category,
            'is_active'         => $this->is_active,
            'show_on_welcome'   => $this->show_on_welcome,
            'show_on_dashboard' => $this->show_on_dashboard,
            'sort_order'        => $this->sort_order,
        ];

        // Replace file if the user uploads a new one
        if ($this->file) {
            if (
                $this->download->file_path &&
                \Storage::disk('public')->exists($this->download->file_path)
            ) {
                \Storage::disk('public')->delete($this->download->file_path);
            }

            $payload['file_path'] = $this->file->store('downloads', 'public');
            $payload['file_name']  = $this->file->getClientOriginalName();
            $payload['file_size']  = $this->file->getSize();
            $payload['mime_type']  = $this->file->getMimeType();
        }

        $this->download->update($payload);
    }
}
