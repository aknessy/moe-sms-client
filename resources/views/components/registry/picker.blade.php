@props([
    'name',
    'label',
    'options' => [],
    'value' => null,
    'required' => false,
    'placeholder' => 'Search by name, file or computer number',
])

@php
    $current = old($name, $value);
    $selectedLabel = $current !== null && $current !== '' ? ($options[$current] ?? '') : '';
    $id = $name . '-' . substr(md5($name . count($options)), 0, 6);

    $control = 'w-full bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md pl-3 pr-9 py-2 transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow';
@endphp

<div class="w-full" data-staff-picker>
    <label for="{{ $id }}" class="font-sans text-sm font-medium text-base-content">
        {{ $label }}@if($required)<span class="text-error">*</span>@endif
    </label>

    <div class="relative mt-1">
        {{-- The value that actually gets submitted. --}}
        <input type="hidden" name="{{ $name }}" value="{{ $current }}" data-picker-value />

        <input type="text"
               id="{{ $id }}"
               autocomplete="off"
               role="combobox"
               aria-expanded="false"
               aria-autocomplete="list"
               value="{{ $selectedLabel }}"
               placeholder="{{ $placeholder }}"
               @required($required)
               data-picker-search
               class="{{ $control }}" />

        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
             class="pointer-events-none absolute w-4 h-4 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
            <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"></path>
            <path d="M21 21l-6 -6"></path>
        </svg>

        <ul data-picker-list hidden role="listbox"
            class="absolute z-30 mt-1 max-h-56 w-full overflow-y-auto rounded-md border border-base-300 bg-white py-1 shadow-lg">
            @foreach ($options as $optionValue => $optionLabel)
                <li>
                    <button type="button"
                            role="option"
                            data-picker-option
                            data-value="{{ $optionValue }}"
                            data-label="{{ $optionLabel }}"
                            data-search="{{ strtolower($optionLabel) }}"
                            class="block w-full px-3 py-2 text-left text-xs text-base-content/80 hover:bg-[#2E7D32]/10 hover:text-[#1B5E20]">
                        {{ $optionLabel }}
                    </button>
                </li>
            @endforeach
            <li data-picker-empty hidden class="px-3 py-3 text-center text-xs text-base-content/50">
                No staff match that search.
            </li>
        </ul>
    </div>

    @error($name)
        <p class="mt-1 text-xs text-error">{{ $message }}</p>
    @enderror
</div>
