<x-layouts::auth :title="__('Lupa Kata Sandi')">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-sm">

            {{-- Card --}}
            <div
                class="rounded-2xl border border-slate-200 dark:border-zinc-800
                        bg-white dark:bg-zinc-950 shadow-sm
                        p-6 space-y-5">

                {{-- Header --}}
                <div class="text-center">
                    <div
                        class="mx-auto w-12 h-12 rounded-2xl
                                bg-emerald-100 dark:bg-emerald-900/40
                                flex items-center justify-center mb-3">
                        <flux:icon.key class="size-6 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <h1 class="font-heading text-base font-semibold text-slate-900 dark:text-zinc-100">
                        {{ __('Lupa Kata Sandi?') }}
                    </h1>
                    <p class="mt-1 text-[11px] text-slate-500 dark:text-zinc-400 leading-relaxed">
                        {{ __('Masukkan email Anda dan kami akan mengirimkan tautan untuk mengatur ulang kata sandi.') }}
                    </p>
                </div>
                {{-- Error (token invalid) --}}
                @if (session('error'))
                    <div
                        class="rounded-lg border border-rose-200 dark:border-rose-900/50
                bg-rose-50 dark:bg-rose-950/30 px-3 py-2.5">
                        <div class="flex items-start gap-2.5">
                            <flux:icon.exclamation-triangle
                                class="size-4 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5" />
                            <div class="flex-1 min-w-0">
                                <p class="text-[11px] font-semibold text-rose-800 dark:text-rose-300">
                                    {{ __('Tautan tidak valid') }}
                                </p>
                                <p class="mt-0.5 text-[10px] text-rose-700 dark:text-rose-400">
                                    {{ session('error') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
                {{-- Session status --}}
                @if (session('status'))
                    <div
                        class="rounded-lg border border-emerald-200 dark:border-emerald-900/50
                                bg-emerald-50 dark:bg-emerald-950/30 px-3 py-2.5">
                        <div class="flex items-start gap-2.5">
                            <flux:icon.check-circle
                                class="size-4 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" />
                            <div class="flex-1 min-w-0">
                                <p class="text-[11px] font-semibold text-emerald-800 dark:text-emerald-300">
                                    {{ __('Link has been sent!') }}
                                </p>
                                <p class="mt-0.5 text-[10px] text-emerald-700 dark:text-emerald-400">
                                    {{ session('status') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Form --}}
                <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-4">
                    @csrf

                    <flux:input name="email" :label="__('Email')" type="email" required autofocus
                        autocomplete="email" icon="envelope" placeholder="nama@email.com" :value="old('email')" />

                    @error('email')
                        <p class="flex items-center gap-1 text-[11px] text-rose-600 dark:text-rose-400 -mt-2">
                            <flux:icon.exclamation-circle class="size-3 shrink-0" />
                            {{ $message }}
                        </p>
                    @enderror

                    <flux:button variant="primary" type="submit" size="sm" class="w-full"
                        data-test="email-password-reset-link-button">
                        {{ __('Kirim Link Reset') }}
                    </flux:button>
                </form>

                {{-- Back to login --}}
                <div class="text-center">
                    <a href="{{ route('login') }}" wire:navigate
                        class="text-[11px] text-slate-500 dark:text-zinc-400
                              hover:text-emerald-600 dark:hover:text-emerald-400 transition">
                        ← {{ __('Kembali ke Login') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-layouts::auth>
