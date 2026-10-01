<x-layouts::auth :title="__('Reset Kata Sandi')">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-sm">

            {{-- Card --}}
            <div class="rounded-2xl border border-slate-200 dark:border-zinc-800
                        bg-white dark:bg-zinc-950 shadow-sm
                        p-6 space-y-5">

                {{-- Header --}}
                <div class="text-center">
                    <div class="mx-auto w-12 h-12 rounded-2xl
                                bg-emerald-100 dark:bg-emerald-900/40
                                flex items-center justify-center mb-3">
                        <flux:icon.lock-closed class="size-6 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <h1 class="font-heading text-base font-semibold text-slate-900 dark:text-zinc-100">
                        {{ __('Reset Kata Sandi') }}
                    </h1>
                    <p class="mt-1 text-[11px] text-slate-500 dark:text-zinc-400 leading-relaxed">
                        {{ __('Masukkan kata sandi baru untuk akun Anda.') }}
                    </p>
                </div>

                {{-- Session status --}}
                @if (session('status'))
                    <div class="rounded-lg border border-emerald-200 dark:border-emerald-900/50
                                bg-emerald-50 dark:bg-emerald-950/30 px-3 py-2.5">
                        <div class="flex items-start gap-2.5">
                            <flux:icon.check-circle class="size-4 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" />
                            <p class="text-[11px] text-emerald-700 dark:text-emerald-400">
                                {{ session('status') }}
                            </p>
                        </div>
                    </div>
                @endif

                {{-- Form --}}
                <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-4">
                    @csrf

                    {{-- Token --}}
                    <input type="hidden" name="token" value="{{ request()->route('token') }}">

                    <div class="pointer-events-none opacity-60">
                        <flux:input
                            name="email"
                            :value="old('email', request('email'))"
                            :label="__('Email')"
                            type="email"
                            required
                            readonly
                            autocomplete="email"
                            icon="envelope"
                        />
                    </div>

                    {{-- Password Baru --}}
                    <flux:input icon="lock-closed"
                        name="password"
                        :label="__('Kata Sandi Baru')"
                        type="password"
                        required
                        autocomplete="new-password"
                        :placeholder="__('Kata Sandi Baru')"
                        passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                        viewable
                    />
                    @error('password')
                        <p class="flex items-center gap-1 text-[11px] text-rose-600 dark:text-rose-400 -mt-2">
                            <flux:icon.exclamation-circle class="size-3 shrink-0" />
                            {{ $message }}
                        </p>
                    @enderror

                    {{-- Konfirmasi --}}
                    <flux:input icon="lock-closed"
                        name="password_confirmation"
                        :label="__('Konfirmasi Kata Sandi')"
                        type="password"
                        required
                        autocomplete="new-password"
                        :placeholder="__('Konfirmasi Kata Sandi')"
                        passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                        viewable
                    />

                    {{-- Submit --}}
                    <flux:button
                        type="submit"
                        variant="primary"
                        size="sm"
                        class="w-full"
                        data-test="reset-password-button">
                        {{ __('Simpan Kata Sandi Baru') }}
                    </flux:button>
                </form>

                {{-- Back to login --}}
                <div class="text-center">
                    <a href="{{ route('login') }}"
                       wire:navigate
                       class="text-[11px] text-slate-500 dark:text-zinc-400
                              hover:text-emerald-600 dark:hover:text-emerald-400 transition">
                        ← {{ __('Kembali ke Login') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-layouts::auth>
