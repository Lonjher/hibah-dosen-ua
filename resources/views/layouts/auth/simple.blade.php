<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
    {{-- Font: DM Sans untuk body, Manrope untuk heading --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|manrope:400,500,600,700,800&display=swap" rel="stylesheet" />
    <style>
        :root {
            --font-heading: 'Manrope', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            --font-body: 'DM Sans', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
        }
        .font-heading {
            font-family: var(--font-heading);
        }
        body, .font-body {
            font-family: var(--font-body);
        }
    </style>
</head>

<body
    class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900 relative overflow-x-hidden font-body">
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
