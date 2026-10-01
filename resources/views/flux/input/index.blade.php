@blaze(fold: true, unsafe: [
    'icon:trailing', 'icon:leading', 'icon:variant', 'mask:dynamic',
    'name', 'label', 'badge',
    'description', 'description:trailing',
    'label:badge', 'label:aside', 'label:trailing',
    'error:name', 'error:bag', 'error:message', 'error:icon', 'error:nested', 'error:deep',
])

@php $iconTrailing ??= $attributes->pluck('icon:trailing'); @endphp
@php $iconLeading ??= $attributes->pluck('icon:leading'); @endphp
@php $iconVariant ??= $attributes->pluck('icon:variant'); @endphp
@php $maskDynamic ??= $attributes->pluck('mask:dynamic'); @endphp

@props([
    'name' => $attributes->whereStartsWith('wire:model')->first(),
    'iconVariant' => 'mini',
    'variant' => 'outline',
    'iconTrailing' => null,
    'iconLeading' => null,
    'maskDynamic' => null,
    'expandable' => null,
    'clearable' => null,
    'copyable' => null,
    'viewable' => null,
    'invalid' => null,
    'loading' => null,
    'type' => 'text',
    'mask' => null,
    'size' => null,
    'icon' => null,
    'kbd' => null,
    'as' => null,
])

@php

$inputAttributes = Flux::attributesAfter('input:', $attributes, []);

$wireModel = $attributes->wire('model');
$wireTarget = null;

if ($loading !== false) {
    if ($loading === true) {
        $loading = true;
    } elseif ($wireModel?->directive) {
        $loading = $wireModel->hasModifier('live');
        $wireTarget = $loading ? $wireModel->value() : null;
    } else {
        $wireTarget = $loading;
        $loading = (bool) $loading;
    }
}

$iconLeading ??= $icon;

$hasLeadingIcon = (bool) ($iconLeading);
$countOfTrailingIcons = collect([
    (bool) $iconTrailing,
    (bool) $kbd,
    (bool) $clearable,
    (bool) $copyable,
    (bool) $viewable,
    (bool) $expandable,
])->filter()->count();

$isDateType = in_array($type, ['date', 'datetime-local', 'time', 'month', 'week'], true);

$iconClasses = Flux::classes()->add('size-3.5');

$inputLoadingClasses = Flux::classes()
    ->add(match ($countOfTrailingIcons) {
        0 => 'pe-7', 1 => 'pe-12', 2 => 'pe-17', 3 => 'pe-22',
        4 => 'pe-27', 5 => 'pe-32', 6 => 'pe-37',
    });

$classes = Flux::classes()
    ->add('w-full border block disabled:shadow-none dark:shadow-none')
    ->add('appearance-none')
    ->add(match ($size) {
        default => 'text-xs rounded-full py-1 h-7 leading-[1rem]',
        'sm'    => 'text-[11px] rounded-full py-0.5 h-6 leading-[0.875rem]',
        'xs'    => 'text-[10px] rounded py-0 h-5 leading-[0.75rem]',
        'lg'    => 'text-sm rounded-full py-1.5 h-8 leading-[1.125rem]',
        'xl'    => 'text-base rounded-lg py-2.5 h-10 leading-[1.375rem]',
    })
    ->add(match ($hasLeadingIcon) {
        true  => 'ps-7',
        false => 'ps-2',
    })
    ->add(match ($countOfTrailingIcons) {
        0 => 'pe-2', 1 => 'pe-7', 2 => 'pe-12', 3 => 'pe-17',
        4 => 'pe-22', 5 => 'pe-27', 6 => 'pe-32',
    })
    ->add(match ($variant) {
        'outline' => 'dark:bg-white/10 dark:disabled:bg-white/[7%]',
        'filled'  => 'bg-zinc-800/5 dark:bg-white/10 dark:disabled:bg-white/[7%]',
    })
    ->add(match ($variant) {
        'outline' => 'text-stone-800 disabled:text-stone-500 placeholder-stone-400 disabled:placeholder-stone-400/70 dark:text-stone-100 dark:disabled:text-stone-400 dark:placeholder-stone-600 dark:disabled:placeholder-stone-500',
        'filled'  => 'text-zinc-700 placeholder-zinc-500 disabled:placeholder-zinc-400 dark:text-zinc-200 dark:placeholder-white/60 dark:disabled:placeholder-white/40',
    })
    ->add(match ($variant) {
        'outline' => 'border-stone-200 border-b-stone-300/80 shadow-xs disabled:border-b-stone-200 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/40 dark:border-stone-700 dark:disabled:border-stone-700/50 dark:focus:border-emerald-400 dark:focus:ring-emerald-400/40',
        'filled'  => 'border-0',
    })
    ->add(match ($variant) {
        'outline' => 'data-invalid:shadow-none data-invalid:border-rose-500 focus:data-invalid:border-rose-500 dark:data-invalid:border-rose-400 dark:focus:data-invalid:border-rose-400 data-invalid:ring-rose-500/40 dark:data-invalid:ring-rose-400/40',
        'filled' => 'data-invalid:border-rose-500'
    })->add($isDateType ? 'flux-input-date' : '');
@endphp

<?php if ($type === 'file'): ?>
    <flux:with-field :$attributes :$name>
        <flux:input.file :$attributes :$name :$size />
    </flux:with-field>
<?php elseif ($as !== 'button'): ?>
    <flux:with-field :$attributes :$name>
        <div {{ $attributes->only('class')->class('w-full relative block group/input') }} data-flux-input>
            <?php if (is_string($iconLeading) && $iconLeading !== ''): ?>
               <div class="pointer-events-none absolute start-0 z-10 -translate-y-1/2 flex items-center justify-center ps-2 text-xs leading-none text-stone-400 dark:text-stone-500 {{ $isDateType ? 'top-[calc(50%-2px)]' : 'top-1/2' }}">
                    <flux:icon :icon="$iconLeading" :variant="$iconVariant" :class="$iconClasses" />
                </div>
                <?php elseif ($iconLeading): ?>
                <div {{ $iconLeading->attributes->class('absolute top-1/2 start-0 z-10 -translate-y-1/2 flex items-center justify-center ps-2 text-xs leading-none text-stone-400 dark:text-stone-500') }}>
                    {{ $iconLeading }}
                </div>
            <?php elseif ($iconLeading): ?>
                <div {{ $iconLeading->attributes->class('absolute inset-y-0 start-0 z-10 flex items-center justify-center ps-2 border-s border-transparent text-xs leading-none text-stone-400 dark:text-stone-500') }}>
                    {{ $iconLeading }}
                </div>
            <?php endif; ?>

            <input
                type="{{ $type }}"
                {{ $attributes->except('class')->class($type === 'file' ? '' : $classes)->merge($inputAttributes->getAttributes()) }}
                <?php if (isset($name)): ?> name="{{ $name }}" <?php endif; ?>
                <?php if ($maskDynamic): ?> x-mask:dynamic="{{ $maskDynamic }}" @elseif ($mask) x-mask="{{ $mask }}" <?php endif; ?>
                <?php if (is_numeric($size)): ?> size="{{ $size }}" <?php endif; ?>
                @unblaze(scope: ['name' => $name ?? null, 'invalid' => $invalid ?? false])
                <?php if ($scope['invalid'] || ($scope['name'] && $errors->has($scope['name']))): ?>
                aria-invalid="true" data-invalid
                <?php endif; ?>
                @endunblaze
                data-flux-control
                data-flux-group-target
                <?php if($loading): ?> wire:loading.class="{{ $inputLoadingClasses }}" <?php endif; ?>
                <?php if($loading && $wireTarget): ?> wire:target="{{ $wireTarget }}" <?php endif; ?>
            >

            <?php if ($loading || $countOfTrailingIcons > 0): ?>
                <div class="absolute inset-y-0 end-0 flex items-center gap-x-1 pe-1 border-e border-transparent text-xs leading-none text-stone-400 pointer-events-none [&>*]:pointer-events-auto">
                    <?php if ($loading): ?>
                        <flux:icon name="loading" :variant="$iconVariant" :class="$iconClasses" wire:loading :wire:target="$wireTarget" />
                    <?php endif; ?>

                    <?php if ($clearable): ?>
                        <flux:input.clearable inset="left right" :$size :$iconVariant />
                    <?php endif; ?>

                    <?php if ($kbd): ?>
                        <span class="pointer-events-none last:pe-1">{{ $kbd }}</span>
                    <?php endif; ?>

                    <?php if ($expandable): ?>
                        <flux:input.expandable inset="left right" :$size :$iconVariant />
                    <?php endif; ?>

                    <?php if ($copyable): ?>
                        <flux:input.copyable inset="left right" :$size :$iconVariant />
                    <?php endif; ?>

                    <?php if ($viewable): ?>
                        <flux:input.viewable inset="left right" :$size :$iconVariant />
                    <?php endif; ?>

                    <?php if (is_string($iconTrailing) && $iconTrailing !== ''): ?>
                        <?php
                            $trailingIconClasses = clone $iconClasses;
                            $trailingIconClasses->add('text-stone-400 dark:text-stone-500 pointer-events-none');
                        ?>
                        <flux:icon :icon="$iconTrailing" :variant="$iconVariant" :class="$trailingIconClasses" />
                    <?php elseif ($iconTrailing): ?>
                        {{ $iconTrailing }}
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </flux:with-field>
<?php else: ?>
    <button {{ $attributes->merge(['type' => 'button'])->class([$classes, 'w-full relative flex']) }}>
        <?php if ($attributes->has('placeholder')): ?>
            <div class="block self-center text-start flex-1 font-medium text-stone-400 dark:text-stone-500">
                {{ $attributes->get('placeholder') }}
            </div>
        <?php else: ?>
            <div class="text-start self-center flex-1 font-medium text-stone-800 dark:text-stone-100">
                {{ $slot }}
            </div>
        <?php endif; ?>

        <?php if ($kbd): ?>
            <div class="absolute inset-y-0 end-0 flex items-center justify-center pe-2 text-xs leading-none text-stone-400">
                {{ $kbd }}
            </div>
        <?php endif; ?>

        <?php if (is_string($iconTrailing) && $iconTrailing !== ''): ?>
            <div class="absolute inset-y-0 end-0 flex items-center justify-center pe-2 text-xs leading-none text-stone-400">
                <flux:icon :icon="$iconTrailing" :variant="$iconVariant" :class="$iconClasses" />
            </div>
        <?php elseif ($iconTrailing): ?>
            <div {{ $iconTrailing->attributes->class('absolute inset-y-0 end-0 flex items-center justify-center pe-1 text-xs leading-none text-stone-400') }}>
                {{ $iconTrailing }}
            </div>
        <?php endif; ?>
    </button>
<?php endif; ?>
