@blaze(fold: true, safe: ['name'])

@props([
    'dismissible' => null,
    'escapable' => null,
    'position' => null,
    'closable' => null,
    'trigger' => null,
    'variant' => null,
    'scroll' => null,
    'flyout' => null,
    'name' => null,
    'title' => null,
    'description' => null,
    'size' => 'xl',
])

@php
$__livewire = $__env->shared('__livewire');

if ($variant === 'flyout') {
    $flyout = true;
    $variant = null;
}

$closable ??= $variant === 'bare' ? false : true;
$overflow = $scroll === 'body' && ! $flyout;

$maxWidthClass = match ($size) {
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
    '3xl' => 'sm:max-w-3xl',
    '4xl' => 'sm:max-w-4xl',
    '5xl' => 'sm:max-w-5xl',
    'full' => 'sm:max-w-full',
    default => 'sm:max-w-xl',
};

if ($flyout) {
    $classes = Flux::classes()
        ->add(match ($variant) {
            default => match($position) {
                'bottom' => 'fixed m-0 p-8 min-w-[100%] overflow-y-auto mt-auto [--flux-flyout-translate:translateY(50px)] border-t',
                'left' => 'fixed m-0 p-8 max-h-dvh min-h-dvh md:[:where(&)]:min-w-[25rem] overflow-y-auto mr-auto [--flux-flyout-translate:translateX(-50px)] border-e rtl:mr-0 rtl:ml-auto rtl:[--flux-flyout-translate:translateX(50px)]',
                default => 'fixed m-0 p-8 max-h-dvh min-h-dvh md:[:where(&)]:min-w-[25rem] overflow-y-auto ml-auto [--flux-flyout-translate:translateX(50px)] border-s rtl:ml-0 rtl:mr-auto rtl:[--flux-flyout-translate:translateX(-50px)]',
            },
            'floating' => match($position) {
                'bottom' => 'fixed m-2 p-8 min-w-[calc(100%-1rem)] overflow-y-auto mt-auto [--flux-flyout-translate:translateY(50px)]',
                'left' => 'fixed m-2 p-8 max-h-[calc(100dvh-1rem)] min-h-[calc(100dvh-1rem)] md:[:where(&)]:min-w-[25rem] overflow-y-auto mr-auto [--flux-flyout-translate:translateX(-50px)] rtl:mr-0 rtl:ml-auto rtl:[--flux-flyout-translate:translateX(50px)]',
                default => 'fixed m-2 p-8 max-h-[calc(100dvh-1rem)] min-h-[calc(100dvh-1rem)] md:[:where(&)]:min-w-[25rem] overflow-y-auto ml-auto [--flux-flyout-translate:translateX(50px)] rtl:ml-0 rtl:mr-auto rtl:[--flux-flyout-translate:translateX(-50px)]',
            },
            'bare' => '',
        })
        ->add(match ($variant) {
            default => 'bg-white dark:bg-stone-900 border-transparent dark:border-stone-700',
            'floating' => 'bg-white dark:bg-stone-900 ring ring-black/5 dark:ring-stone-700 shadow-lg rounded-xl',
            'bare' => 'bg-transparent',
        });
} elseif ($overflow) {
    $classes = Flux::classes();

    $contentClasses = Flux::classes()
        ->add('relative')
        ->add(match ($variant) {
            default => 'p-4 ' . $maxWidthClass . ' [:where(&)]:min-w-xs shadow-lg rounded-lg text-xs',
            'bare' => '',
        })
        ->add(match ($variant) {
            default => 'bg-white dark:bg-stone-900 ring ring-black/5 dark:ring-stone-700 shadow-lg rounded-lg',
            'bare' => 'bg-transparent',
        });
} else {
    $classes = Flux::classes()
        ->add(match ($variant) {
            default => 'p-4 ' . $maxWidthClass . ' [:where(&)]:min-w-xs shadow-lg rounded-lg text-xs',
            'bare' => '',
        })
        ->add(match ($variant) {
            default => 'bg-white dark:bg-stone-900 ring ring-black/5 dark:ring-stone-700 shadow-lg rounded-lg',
            'bare' => 'bg-transparent',
        });
}

// Support adding the .self modifier to the wire:model directive...
if (($wireModel = $attributes->wire('model')) && $wireModel->directive && ! $wireModel->hasModifier('self')) {
    unset($attributes[$wireModel->directive]);

    $wireModel->directive .= '.self';

    $attributes = $attributes->merge([$wireModel->directive => $wireModel->value]);
}

if ($attributes['@close'] ?? null) {
    $attributes['wire:close'] = $attributes['@close'];

    unset($attributes['@close']);
}

if ($attributes['@cancel'] ?? null) {
    $attributes['wire:cancel'] = $attributes['@cancel'];

    unset($attributes['@cancel']);
}

if ($dismissible === false) {
    $attributes = $attributes->merge(['disable-click-outside' => '']);
}

if ($escapable === false) {
    $attributes = $attributes->merge(['disable-escape' => '']);
}

[ $contentAttributes, $attributes ] = Flux::splitAttributes($attributes, ['autofocus', 'class', 'style']);
[ $dialogAttributes, $attributes ] = Flux::splitAttributes($attributes, ['wire:close', 'x-on:close', 'wire:cancel', 'x-on:cancel']);

if (! $overflow) {
    $dialogAttributes = $dialogAttributes->merge($contentAttributes->getAttributes());
}
@endphp

<ui-modal {{ $attributes }} data-flux-modal>
    <?php if ($trigger): ?>
        {{ $trigger }}
    <?php endif; ?>

    <dialog
        wire:ignore.self
        {{ $dialogAttributes->class($classes) }}
        @if ($name) data-modal="{{ $name }}" @endif
        @if ($flyout) data-flux-flyout @endif
        @if ($overflow) data-flux-modal-overflow @endif
        @unblaze(scope: ['name' => $name])
        x-data="fluxModal(@js($scope['name']), @js(isset($__livewire) ? $__livewire->getId() : null))"
        @endunblaze
        x-on:modal-show.document="handleShow($event)"
        x-on:modal-close.document="handleClose($event)"
    >
        @if ($overflow)
            <div class="flex min-h-full items-center justify-center p-4 sm:p-6">
                <div {{ $contentAttributes->class($contentClasses) }} data-flux-modal-content>
                    @if ($title || $description)
                        <div class="mb-4 border-b border-stone-100 pb-3 dark:border-stone-800">
                            <div class="flex items-start justify-between">
                                <div>
                                    @if ($title)
                                        <h3 class="text-sm font-semibold text-stone-800 dark:text-stone-100">{{ $title }}</h3>
                                    @endif
                                    @if ($description)
                                        <p class="mt-0.5 text-[11px] leading-normal text-stone-500 dark:text-stone-400">{{ $description }}</p>
                                    @endif
                                </div>
                                @if ($closable)
                                    <flux:modal.close>
                                        <flux:button variant="ghost" icon="x-mark" size="sm" aria-label="{{ __('Close modal') }}" class="!text-stone-400 hover:!text-stone-800 dark:!text-stone-500 dark:hover:!text-white"></flux:button>
                                    </flux:modal.close>
                                @endif
                            </div>
                        </div>
                    @else
                        @if ($closable)
                            <div class="absolute top-0 end-0 mt-4 me-4">
                                <flux:modal.close>
                                    <flux:button variant="ghost" icon="x-mark" size="sm" aria-label="{{ __('Close modal') }}" class="!text-stone-400 hover:!text-stone-800 dark:!text-stone-500 dark:hover:!text-white"></flux:button>
                                </flux:modal.close>
                            </div>
                        @endif
                    @endif

                    {{ $slot }}
                </div>
            </div>
        @else
            @if ($title || $description)
                <div class="mb-4 border-b border-stone-100 pb-3 dark:border-stone-800">
                    <div class="flex items-start justify-between">
                        <div>
                            @if ($title)
                                <h3 class="text-sm font-semibold text-stone-800 dark:text-stone-100">{{ $title }}</h3>
                            @endif
                            @if ($description)
                                <p class="mt-0.5 text-[11px] leading-normal text-stone-500 dark:text-stone-400">{{ $description }}</p>
                            @endif
                        </div>
                        @if ($closable)
                            <flux:modal.close>
                                <flux:button variant="ghost" icon="x-mark" size="sm" aria-label="{{ __('Close modal') }}" class="!text-stone-400 hover:!text-stone-800 dark:!text-stone-500 dark:hover:!text-white"></flux:button>
                            </flux:modal.close>
                        @endif
                    </div>
                </div>
            @else
                @if ($closable)
                    <div class="absolute top-0 end-0 mt-4 me-4">
                        <flux:modal.close>
                            <flux:button variant="ghost" icon="x-mark" size="sm" aria-label="{{ __('Close modal') }}" class="!text-stone-400 hover:!text-stone-800 dark:!text-stone-500 dark:hover:!text-white"></flux:button>
                        </flux:modal.close>
                    </div>
                @endif
            @endif

            {{ $slot }}
        @endif
    </dialog>
</ui-modal>
