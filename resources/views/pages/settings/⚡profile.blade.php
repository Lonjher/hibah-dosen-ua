<?php

use App\Concerns\PasswordValidationRules;
use App\Livewire\Forms\UserForm;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Flux\Flux;
use Livewire\Attributes\On;

new #[Title('Profile settings')] class extends Component {
    use PasswordValidationRules;
    use WithFileUploads;

    /* ── Form object (personal info) ── */
    public UserForm $form;
    /* ── Avatar ── */
    public ?TemporaryUploadedFile $avatar = null;
    public ?string $existingAvatarUrl = null;
    /* ── Password (terpisah, karena butuh current_password) ── */
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';
    /* ── Email code verification ── */
    public bool $showVerificationModal = false;
    public string $verification_code = '';

    /* ── Verification state tracking ── */
    public bool $wasUnverified = false;

    public function mount(): void
    {
        $user = Auth::user();

        $this->form->setUser($user);

        $this->existingAvatarUrl = $user->avatar ? asset('storage/' . $user->avatar) : null;
        $this->wasUnverified = $this->hasUnverifiedEmail;
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();
        $oldEmail = $user->email;

        // Avatar divalidasi terpisah (bukan bagian dari UserForm)
        if ($this->avatar) {
            $this->validate(
                [
                    'avatar' => ['nullable', 'image', 'max:2048'],
                ],
                [
                    'avatar.image' => 'File must be an image.',
                    'avatar.max' => 'The image must be 2MB in maximun size.',
                ],
            );
        }
        // Validasi + simpan via UserForm (menggunakan rules & messages dari form)
        $this->form->update();
        $user = $user->fresh();
        // Reset verifikasi email kalau email berubah
        if ($user->email !== $oldEmail) {
            $user->email_verified_at = null;
            $user->save();
        }
        // Handle upload avatar
        if ($this->avatar) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = $this->avatar->store('avatars', 'public');
            $user->save();
        }
        // Re-load form dengan data user yang fresh (form->update() sudah reset state)
        $this->form->setUser($user);
        $this->avatar = null;
        $this->existingAvatarUrl = $user->avatar ? asset('storage/' . $user->avatar) : null;
        $this->dispatch('avatar-updated');
        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

    public function removeAvatar(): void
    {
        $user = Auth::user();

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->update(['avatar' => null]);

        $this->avatar = null;
        $this->existingAvatarUrl = null;

        $this->dispatch('avatar-updated');

        Flux::toast(variant: 'success', text: __('Avatar removed.'));
    }

    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');
            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
            'raw_password' => $this->password,
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        Flux::toast(variant: 'success', text: __('Password updated.'));
    }

    /**
     * Buka modal & kirim kode verifikasi.
     */
    public function openVerificationModal(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));
            return;
        }

        $this->sendVerificationCode();

        $this->verification_code = '';
        $this->resetErrorBag('verification_code');
        $this->showVerificationModal = true;
    }

    /**
     * Kirim ulang kode (dari dalam modal).
     */
    public function resendVerificationCode(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            return;
        }

        $this->sendVerificationCode();
    }

    #[On('email-verified')]
    public function onEmailVerified(): void
    {
        $this->wasUnverified = false;
        unset($this->hasUnverifiedEmail);
    }

    /**
     * Verifikasi kode yang diinput user.
     */
    public function verifyEmailCode(): void
    {
        $this->validate(
            [
                'verification_code' => ['required', 'string', 'digits:6'],
            ],
            [
                'verification_code.required' => 'Kode verifikasi wajib diisi.',
                'verification_code.digits' => 'Kode verifikasi harus 6 digit angka.',
            ],
        );

        $user = Auth::user();

        if (!$user->verifyEmailWithCode($this->verification_code)) {
            $this->addError('verification_code', 'Kode verifikasi tidak valid atau sudah kedaluwarsa.');
            return;
        }

        $this->showVerificationModal = false;
        $this->verification_code = '';
        $this->wasUnverified = false;

        unset($this->hasUnverifiedEmail);

        Flux::toast(variant: 'success', text: __('Email berhasil diverifikasi.'));
    }

    /**
     * Tutup modal.
     */
    public function closeVerificationModal(): void
    {
        $this->showVerificationModal = false;
        $this->verification_code = '';
        $this->resetErrorBag('verification_code');
    }

    /**
     * Cek apakah email sudah terverifikasi (dipanggil via wire:poll).
     */
    public function checkEmailVerification(): void
    {
        $user = Auth::user()->fresh();

        if ($this->wasUnverified && $user->hasVerifiedEmail()) {
            $this->wasUnverified = false;
            Flux::toast(variant: 'success', text: __('Email berhasil diverifikasi.'));
        }

        unset($this->hasUnverifiedEmail);
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && !Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function userInitials(): string
    {
        $name = $this->form->full_name ?: 'U';
        $parts = preg_split('/\s+/', trim($name));

        if (count($parts) >= 2) {
            return strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
        }

        return strtoupper(mb_substr($name, 0, 2));
    }

    #[Computed]
    public function memberSince(): string
    {
        return Auth::user()->created_at?->translatedFormat('F Y') ?? '—';
    }
}; ?>

<section class="w-full" @if ($this->hasUnverifiedEmail) wire:poll.15s="checkEmailVerification" @endif>
    <x-pages::settings.layout>
        <div class="m-4 space-y-4 text-xs">

            <div
                class="overflow-hidden rounded-full border border-slate-200 dark:border-zinc-800
                        bg-white dark:bg-zinc-950 shadow-sm">

                {{-- Banner --}}
                <div class="banner-animated relative h-20 sm:h-24 overflow-hidden">

                    {{-- ── Animated blobs (light: pastel; dark: muted) ── --}}
                    <div
                        class="banner-blob-1 absolute -top-8 -left-8 h-32 w-32 sm:h-40 sm:w-40
                rounded-full blur-3xl
                bg-violet-300/50 dark:bg-violet-600/30">
                    </div>
                    <div
                        class="banner-blob-2 absolute -bottom-10 -right-6 h-32 w-32 sm:h-40 sm:w-40
                rounded-full blur-3xl
                bg-teal-300/50 dark:bg-teal-600/25">
                    </div>
                    <div class="banner-blob-1 absolute top-1/2 left-1/2 h-24 w-24 sm:h-32 sm:w-32
                -translate-x-1/2 -translate-y-1/2
                rounded-full blur-3xl
                bg-blue-300/40 dark:bg-blue-700/25"
                        style="animation-delay: -3s;">
                    </div>

                    {{-- ── Texture layers ── --}}
                    <div class="banner-texture-dots     absolute inset-0 pointer-events-none"></div>
                    <div class="banner-texture-noise    absolute inset-0 pointer-events-none"></div>
                    <div class="banner-texture-vignette absolute inset-0 pointer-events-none"></div>

                    {{-- ── Radial highlights ── --}}
                    <div class="absolute inset-0 pointer-events-none opacity-30
                dark:opacity-15"
                        style="background-image:
            radial-gradient(circle at 20% 30%, rgba(255,255,255,0.7) 0, transparent 45%),
            radial-gradient(circle at 80% 70%, rgba(255,255,255,0.5) 0, transparent 40%);">
                    </div>

                    {{-- ── Shine sweep ── --}}
                    <div class="banner-shine absolute inset-y-0 w-1/3 pointer-events-none"></div>

                    {{-- ── Border bawah halus ── --}}
                    <div
                        class="absolute bottom-0 inset-x-0 h-px
                bg-gradient-to-r from-transparent via-white/40 to-transparent
                dark:via-white/15">
                    </div>
                </div>

                {{-- Avatar + Info --}}
                <div class="relative px-4 sm:px-5 pb-4">
                    <div class="flex flex-col items-center gap-3 sm:flex-row sm:items-end sm:gap-4">

                        {{-- Avatar --}}
                        <div class="-mt-10 shrink-0 sm:-mt-12">
                            <div x-data="{
                                previewUrl: null,
                                fileName: '',
                                existingUrl: @js($existingAvatarUrl),
                                handleFile(e) {
                                    const file = e.target.files[0];
                                    if (!file) {
                                        this.previewUrl = null;
                                        this.fileName = '';
                                        return;
                                    }
                                    this.fileName = file.name;
                                    const reader = new FileReader();
                                    reader.onload = ev => { this.previewUrl = ev.target.result; };
                                    reader.readAsDataURL(file);
                                },
                                get currentImage() { return this.previewUrl || this.existingUrl; }
                            }" x-init="$watch('$wire.existingAvatarUrl', value => {
                                existingUrl = value;
                                previewUrl = null;
                                fileName = '';
                            });"
                                @avatar-updated.window="
                                    existingUrl = @js($existingAvatarUrl);
                                    previewUrl  = null;
                                    fileName    = '';
                                ">
                                <div class="group relative">
                                    {{-- Preview --}}
                                    <template x-if="currentImage">
                                        <img :src="currentImage" alt="{{ $form->full_name }}"
                                            x-on:error="existingUrl = null"
                                            class="h-20 w-20 sm:h-24 sm:w-24 rounded-full object-cover
                                                border-white dark:border-zinc-950
                                                   shadow-lg ring-1 ring-slate-200 dark:ring-zinc-800" />
                                    </template>

                                    {{-- Initials fallback --}}
                                    <template x-if="!currentImage">
                                        <div
                                            class="flex h-20 w-20 sm:h-24 sm:w-24 items-center justify-center
                                                    rounded-full border-white dark:border-zinc-950
                                                    bg-gradient-to-br from-violet-500 to-indigo-600
                                                    text-xl sm:text-2xl font-bold text-white
                                                    shadow-lg ring-1 ring-slate-200 dark:ring-zinc-800">
                                            {{ $this->userInitials }}
                                        </div>
                                    </template>

                                    {{-- Hover overlay --}}
                                    <label for="avatar-upload"
                                        class="absolute inset-0 flex cursor-pointer items-center justify-center
                                               rounded-full bg-black/50 opacity-0 transition-opacity
                                               group-hover:opacity-100">
                                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor"
                                            stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
                                        </svg>
                                    </label>

                                    <input x-ref="fileInput" wire:model="avatar" type="file" accept="image/*"
                                        class="hidden" id="avatar-upload" x-on:change="handleFile($event)" />
                                </div>

                                {{-- Loading --}}
                                <div wire:loading wire:target="avatar"
                                    class="mt-1.5 flex items-center justify-center gap-1
                                           text-[10px] text-violet-600 dark:text-violet-400">
                                    <svg class="h-3 w-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                    </svg>
                                    {{ __('Mengupload...') }}
                                </div>

                                @error('avatar')
                                    <p class="mt-1 text-center text-[10px] text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Info --}}
                        <div class="flex-1 text-center sm:pb-1 sm:text-left min-w-0">
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-zinc-100 truncate">
                                {{ $form->full_name ?: __('User') }}
                            </h3>
                            <p class="mt-0.5 text-[11px] text-slate-500 dark:text-zinc-400 truncate">
                                {{ $form->email ?: '—' }}
                            </p>
                            <p class="mt-0.5 text-[10px] text-slate-400 dark:text-zinc-500">
                                {{ __('Member since :month', ['month' => $this->memberSince]) }}
                            </p>

                            <div class="mt-1.5 flex flex-wrap items-center justify-center sm:justify-start gap-1.5">
                                @if ($this->hasUnverifiedEmail)
                                    <span
                                        class="inline-flex items-center gap-1 text-[10px] font-medium
                                                 px-1.5 py-0.5 rounded-full
                                                 bg-amber-100 text-amber-700
                                                 dark:bg-amber-900/40 dark:text-amber-300">
                                        <svg class="w-2.5 h-2.5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 6a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 6Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        {{ __('Unverified') }}
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center gap-1 text-[10px] font-medium
                                                 px-1.5 py-0.5 rounded-full
                                                 bg-emerald-100 text-emerald-700
                                                 dark:bg-emerald-900/40 dark:text-emerald-300">
                                        <svg class="w-2.5 h-2.5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        {{ __('Verified') }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Action buttons --}}
                        <div class="flex items-center gap-1.5 sm:pb-1">
                            <label for="avatar-upload"
                                class="inline-flex cursor-pointer items-center gap-1
                                       rounded-full border border-slate-200 dark:border-zinc-800
                                       bg-white dark:bg-zinc-900
                                       px-2.5 py-1 text-[10px] font-medium
                                       text-slate-700 dark:text-zinc-300
                                       hover:bg-slate-50 dark:hover:bg-zinc-800 transition">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
                                </svg>
                                {{ $existingAvatarUrl ? __('Change') : __('Upload') }}
                            </label>

                            @if ($existingAvatarUrl)
                                <button type="button" wire:click="removeAvatar"
                                    wire:confirm="{{ __('Hapus avatar?') }}"
                                    class="inline-flex cursor-pointer items-center gap-1
                                           rounded-full border border-red-200 dark:border-red-900/50
                                           bg-white dark:bg-zinc-900
                                           px-2.5 py-1 text-[10px] font-medium
                                           text-red-600 dark:text-red-400
                                           hover:bg-red-50 dark:hover:bg-red-950/30 transition">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                    {{ __('Delete') }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Email verification notice --}}
            @if ($this->hasUnverifiedEmail)
                <div class="sm:col-span-2">
                    <div
                        class="rounded-xl shadow-sm border border-amber-200 dark:border-amber-900/50
                                            bg-amber-50 dark:bg-amber-950/30 px-3 py-2.5">
                        <div class="flex items-start gap-2.5">
                            <div
                                class="shrink-0 w-6 h-6 rounded-md
                                                    bg-amber-100 dark:bg-amber-900/50
                                                    flex items-center justify-center">
                                <svg class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" fill="none"
                                    stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[11px] font-semibold text-amber-800 dark:text-amber-300">
                                    {{ __('Email has not been verified') }}
                                </p>
                                <p class="mt-0.5 text-[10px] text-amber-700 dark:text-amber-400">
                                    {{ __('Please verify your email to secure your account!') }}
                                </p>
                                <button type="button"
                                    onclick="window.dispatchEvent(new CustomEvent('open-email-verification-modal'))"
                                    class="mt-1.5 text-[10px] font-medium underline
                                        text-amber-800 hover:text-amber-900
                                        dark:text-amber-300 dark:hover:text-amber-200">
                                    {{ __('Verifikasi Email Sekarang') }}
                                </button>

                                @if (session('status') === 'verification-link-sent')
                                    <p class="mt-1.5 text-[10px] font-medium text-emerald-600 dark:text-emerald-400">
                                        {{ __('A new verification link has been sent to your account!') }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ═══════════════════════════════════════════ --}}
            {{-- PERSONAL INFORMATION FORM                    --}}
            {{-- ═══════════════════════════════════════════ --}}
            <div
                class="overflow-hidden rounded-full border border-slate-200 dark:border-zinc-800
                        bg-white dark:bg-zinc-950 shadow-sm">

                <div
                    class="flex items-center gap-2.5 border-b border-slate-100 dark:border-zinc-800/70 px-4 sm:px-5 py-3">
                    <div
                        class="flex h-7 w-7 items-center justify-center rounded-lg
                                bg-violet-100 dark:bg-violet-900/40">
                        <svg class="h-3.5 w-3.5 text-violet-700 dark:text-violet-400" fill="none"
                            stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800 dark:text-zinc-100">
                            {{ __('Personal Information') }}
                        </h3>
                        <p class="text-[10px] text-slate-500 dark:text-zinc-400">
                            {{ __('Update your personal details and contact information') }}
                        </p>
                    </div>
                </div>

                <form wire:submit="updateProfileInformation" class="p-4 sm:p-5 space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        {{-- Full name --}}
                        <flux:input wire:model="form.full_name" :label="__('Full Name')" type="text" required
                            autofocus autocomplete="name" icon="user" />

                        {{-- Email --}}
                        <flux:input wire:model.live="form.email" :label="__('Email')" type="email" required
                            autocomplete="email" icon="envelope" />

                        {{-- Birthday --}}
                        <flux:input wire:model="form.birthday" :label="__('Birthday')" type="date" required
                            icon="calendar" />

                        {{-- Gender --}}
                        <flux:select wire:model="form.gender" :label="__('Gender')" required>
                            <flux:select.option value="">{{ __('Select Gender') }}</flux:select.option>
                            <flux:select.option value="laki-laki">{{ __('Male') }}</flux:select.option>
                            <flux:select.option value="perempuan">{{ __('Female') }}</flux:select.option>
                        </flux:select>

                        {{-- Phone --}}
                        <div class="sm:col-span-2">
                            <flux:input wire:model="form.phone_number" :label="__('Phone Number')" type="text"
                                autocomplete="tel" icon="phone" />
                        </div>

                        {{-- Address (full width) --}}
                        <div class="sm:col-span-2">
                            <flux:textarea wire:model="form.address" :label="__('Address')" rows="4" required
                                size="sm" />
                        </div>
                    </div>

                    <div class="flex justify-end border-t border-slate-100 dark:border-zinc-800/70 pt-4">
                        <flux:button variant="primary" type="submit" size="sm"
                            data-test="update-profile-button" wire:loading.attr="disabled"
                            wire:target="updateProfileInformation">
                            <span wire:loading.remove wire:target="updateProfileInformation">
                                {{ __('Submit') }}
                            </span>
                            <span wire:loading wire:target="updateProfileInformation">
                                {{ __('Saving...') }}
                            </span>
                        </flux:button>
                    </div>
                </form>
            </div>

            {{-- ═══════════════════════════════════════════ --}}
            {{-- PASSWORD FORM                                --}}
            {{-- ═══════════════════════════════════════════ --}}
            <div
                class="overflow-hidden rounded-full border border-slate-200 dark:border-zinc-800
                        bg-white dark:bg-zinc-950 shadow-sm">

                <div
                    class="flex items-center gap-2.5 border-b border-slate-100 dark:border-zinc-800/70 px-4 sm:px-5 py-3">
                    <div
                        class="flex h-7 w-7 items-center justify-center rounded-lg
                                bg-violet-100 dark:bg-violet-900/40">
                        <svg class="h-3.5 w-3.5 text-violet-700 dark:text-violet-400" fill="none"
                            stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800 dark:text-zinc-100">
                            {{ __('Change Password') }}
                        </h3>
                        <p class="text-[10px] text-slate-500 dark:text-zinc-400">
                            {{ __('Use unique and long password to keep your account save.') }}
                        </p>
                    </div>
                </div>

                <form wire:submit="updatePassword" class="p-4 sm:p-5">
                    <div class="space-y-4 max-w-lg">
                        <flux:input wire:model="current_password" :label="__('Recent Password')" type="password"
                            required autocomplete="current-password" viewable />

                        <flux:input wire:model="password" :label="__('New Password')" type="password" required
                            autocomplete="new-password"
                            passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                            viewable />

                        <flux:input wire:model="password_confirmation" :label="__('Password Confirmation')"
                            type="password" required autocomplete="new-password"
                            passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                            viewable />
                    </div>

                    <div class="mt-5 flex justify-end border-t border-slate-100 dark:border-zinc-800/70 pt-4 max-w-lg">
                        <flux:button variant="primary" type="submit" size="sm"
                            data-test="update-password-button" wire:loading.attr="disabled"
                            wire:target="updatePassword">
                            <span wire:loading.remove wire:target="updatePassword">
                                {{ __('Submit') }}
                            </span>
                            <span wire:loading wire:target="updatePassword">
                                {{ __('Saving...') }}
                            </span>
                        </flux:button>
                    </div>
                </form>
            </div>
        </div>
        <livewire:email-verification-modal />
    </x-pages::settings.layout>
</section>
