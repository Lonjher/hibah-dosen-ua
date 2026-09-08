<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body
    class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900 relative overflow-x-hidden">
    {{-- Background pattern & dekorasi --}}
    <div class="pointer-events-none fixed inset-0 z-0 bg-pattern-dots"></div>
    <div
        class="pointer-events-none fixed -top-40 -left-20 z-0 h-[550px] w-[550px] rounded-full bg-gradient-to-br from-emerald-100/50 to-teal-100/30 blur-3xl dark:from-emerald-900/20 dark:to-teal-900/10">
    </div>
    <div
        class="pointer-events-none fixed top-96 -right-24 z-0 h-[600px] w-[600px] rounded-full bg-gradient-to-bl from-teal-100/40 to-emerald-100/20 blur-3xl dark:from-emerald-900/10 dark:to-transparent">
    </div>

    {{-- Konten utama --}}
    <div class="relative z-10">
        {{ $slot }}
    </div>

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @fluxScripts
</body>

</html>
