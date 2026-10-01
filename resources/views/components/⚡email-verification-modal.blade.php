<?php

use App\Mail\EmailVerificationCodeMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Flux\Flux;

new class extends Component {
    public string $verification_code = '';
    public string $email = '';

    public function sendCode(): void
    {
        $user = Auth::user();

        if (! $user || $user->hasVerifiedEmail()) {
            return;
        }

        $code = $user->generateEmailVerificationCode();

        Mail::to($user->email)->send(
            new EmailVerificationCodeMail($code, $user->full_name)
        );

        $this->email = $user->email;
        $this->verification_code = '';
        $this->resetErrorBag('verification_code');

        Flux::toast(variant: 'success', text: __('Kode verifikasi telah dikirim ke email Anda.'));
    }

    public function resend(): void
    {
        $this->sendCode();
    }

    public function verify(): void
    {
        $this->validate([
            'verification_code' => ['required', 'digits:6'],
        ], [
            'verification_code.required' => 'Kode verifikasi wajib diisi.',
            'verification_code.digits' => 'Kode verifikasi harus 6 digit angka.',
        ]);

        $user = Auth::user();

        if (! $user->verifyEmailWithCode($this->verification_code)) {
            $this->addError('verification_code', 'Kode tidak valid atau sudah kedaluwarsa.');
            return;
        }

        $this->verification_code = '';
        $this->resetErrorBag('verification_code');

        $this->dispatch('email-verified');
        $this->dispatch('close-email-verification-modal');

        Flux::toast(variant: 'success', text: __('Email berhasil diverifikasi.'));
    }

    public function close(): void
    {
        $this->verification_code = '';
        $this->resetErrorBag('verification_code');
    }
};
?>

<div
    x-data="{ show: false }"
    x-init="
        window.addEventListener('open-email-verification-modal', () => {
            show = true;
            $wire.sendCode();
        });
        window.addEventListener('close-email-verification-modal', () => { show = false; });
    "
    x-show="show"
    x-transition.opacity
    x-cloak
    @keydown.escape.window="show = false; $wire.close()"
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4"
    @click.self="show = false; $wire.close()">

    <div class="flex max-h-[90vh] w-full sm:max-w-md flex-col overflow-hidden
                bg-white dark:bg-zinc-900 rounded-t-2xl sm:rounded-xl shadow-2xl
                border border-slate-200 dark:border-zinc-700"
        @click.stop>

        {{-- Header --}}
        <div class="shrink-0 bg-gradient-to-r from-amber-500 to-orange-500
                    px-5 sm:px-6 py-4 rounded-t-2xl sm:rounded-t-xl">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-2.5 min-w-0 flex-1">
                    <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center shrink-0">
                        <flux:icon.envelope class="size-4 text-white" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="font-heading text-[15px] font-semibold text-white leading-tight">
                            Verifikasi Email
                        </h3>
                        <p class="text-[11px] text-white/75 mt-0.5 leading-snug">
                            Masukkan 6 digit kode yang dikirim ke email Anda.
                        </p>
                    </div>
                </div>
                <button type="button" @click="show = false; $wire.close()"
                    class="shrink-0 w-7 h-7 rounded-lg flex items-center justify-center
                           text-white/80 hover:text-white hover:bg-white/10 transition-colors">
                    <flux:icon.x-mark class="size-4" />
                </button>
            </div>
        </div>

        {{-- Body --}}
        <form wire:submit.prevent="verify" class="flex min-h-0 flex-1 flex-col">
            <div class="flex-1 overflow-y-auto px-5 sm:px-6 py-5 space-y-4">

                <div class="text-center">
                    <p class="text-[11px] text-slate-500 dark:text-zinc-400">
                        Kode dikirim ke
                    </p>
                    <p class="text-[12px] font-medium text-slate-700 dark:text-zinc-200 mt-0.5 break-all">
                        {{ $email }}
                    </p>
                </div>

                {{-- Kode Input --}}
                <div>
                    <input
                        type="text"
                        wire:model="verification_code"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        maxlength="6"
                        autocomplete="one-time-code"
                        placeholder="000000"
                        autofocus
                        class="block w-full text-center tracking-[0.5em] font-mono text-lg font-semibold
                               py-2.5 rounded-xl
                               border-slate-300 dark:border-zinc-600
                               bg-white dark:bg-zinc-800
                               text-slate-900 dark:text-zinc-100
                               placeholder:text-slate-300 dark:placeholder:text-zinc-600
                               focus:border-amber-500 focus:ring-amber-500/40 focus:ring-2
                               focus:outline-none" />
                    @error('verification_code')
                        <p class="mt-2 flex items-center justify-center gap-1 text-[11px] text-rose-600 dark:text-rose-400">
                            <flux:icon.exclamation-circle class="size-3 shrink-0" />
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Resend --}}
                <div class="text-center">
                    <span class="text-[11px] text-slate-500 dark:text-zinc-400">
                        Tidak menerima kode?
                    </span>
                    <button type="button"
                        wire:click="resend"
                        wire:loading.attr="disabled"
                        wire:target="resend"
                        class="ml-1 text-[11px] font-medium text-amber-600 dark:text-amber-400
                               hover:text-amber-700 dark:hover:text-amber-300 underline
                               disabled:opacity-50 disabled:cursor-wait">
                        <span wire:loading.remove wire:target="resend">Kirim ulang</span>
                        <span wire:loading wire:target="resend">Mengirim...</span>
                    </button>
                </div>
            </div>

            {{-- Footer --}}
            <div class="shrink-0 flex flex-col-reverse sm:flex-row sm:justify-end gap-2
                        px-5 sm:px-6 py-4 border-t border-slate-200 dark:border-zinc-700
                        bg-white dark:bg-zinc-900 rounded-b-2xl sm:rounded-b-xl">
                <flux:button type="button" @click="show = false; $wire.close()" variant="ghost" size="sm">
                    Batal
                </flux:button>
                <flux:button type="submit" variant="primary" size="sm"
                    wire:loading.attr="disabled" wire:target="verify" icon="check">
                    <span wire:loading.remove wire:target="verify">Verifikasi</span>
                    <span wire:loading wire:target="verify">Memverifikasi...</span>
                </flux:button>
            </div>
        </form>
    </div>
</div>
