<x-layouts::auth :title="__('Verifikasi Email')">
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
                        <flux:icon.envelope class="size-6 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <h1 class="font-heading text-base font-semibold text-slate-900 dark:text-zinc-100">
                        {{ __('Verify your Email Address') }}
                    </h1>
                    <p class="mt-1 text-[11px] text-slate-500 dark:text-zinc-400 leading-relaxed">
                        {{ __('We have emailed your verification link. Please check your inbox or spam folder.') }}
                    </p>
                </div>

                {{-- Status: link terkirim --}}
                @if (session('status') === 'verification-link-sent')
                    <div class="rounded-lg border border-emerald-200 dark:border-emerald-900/50
                                bg-emerald-50 dark:bg-emerald-950/30 px-3 py-2.5">
                        <div class="flex items-start gap-2.5">
                            <flux:icon.check-circle class="size-4 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" />
                            <p class="text-[11px] text-emerald-700 dark:text-emerald-400">
                                {{ __('Verification link has been sent to your account!') }}
                            </p>
                        </div>
                    </div>
                @endif

                {{-- Actions --}}
                <div class="flex flex-col gap-3">

                    {{-- Kirim ulang --}}
                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <flux:button type="submit" variant="primary" size="sm"
                            class="w-full" data-test="resend-verification-button">
                            {{ __('Resend Verification Email') }}
                        </flux:button>
                    </form>

                    {{-- Logout --}}
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="w-full text-center text-[11px] text-slate-500 dark:text-zinc-400
                                   hover:text-emerald-600 dark:hover:text-emerald-400 transition"
                            data-test="logout-button">
                            {{ __('Logout') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts::auth>
