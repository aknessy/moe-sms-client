@props([
    'name',
    'label',
    'type' => 'text',
    'options' => null,   // [value => label] turns this into a select
    'rows' => null,      // set to render a textarea
    'value' => null,
    'required' => false,
    'placeholder' => '',
])

@php
    $control = 'w-full bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md px-3 py-2 transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow';
    $current = old($name, $value);
@endphp

<div class="w-full">
    <label for="{{ $name }}" class="font-sans text-sm font-medium text-base-content">
        {{ $label }}@if($required)<span class="text-error">*</span>@endif
    </label>
    <div class="relative mt-1">
        @if ($options !== null)
            <select id="{{ $name }}" name="{{ $name }}" @required($required) class="{{ $control }} cursor-pointer">
                <option value="">Select Option</option>
                @foreach ($options as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" @selected((string) $current === (string) $optionValue)>{{ $optionLabel }}</option>
                @endforeach
            </select>
        @elseif ($rows !== null)
            <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" placeholder="{{ $placeholder }}"
                      @required($required) class="{{ $control }} resize-none">{{ $current }}</textarea>
        @else
            <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $current }}"
                   placeholder="{{ $placeholder }}" @required($required) class="{{ $control }}" />
        @endif
    </div>
    @error($name)
        <p class="mt-1 text-xs text-error">{{ $message }}</p>
    @enderror
</div>
