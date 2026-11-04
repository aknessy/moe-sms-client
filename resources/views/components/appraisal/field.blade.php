@props(['label', 'value' => null, 'hint' => null])

{{-- A field the report restates rather than asks for: it is drawn from the
     staff record, and the Registry is where it would be corrected. --}}
<div class="w-full">
    <label class="font-sans text-sm font-medium text-base-content">{{ $label }}</label>
    <div class="relative mt-1">
        <input type="text" readonly value="{{ filled($value) ? $value : '' }}"
               placeholder="Not on record"
               class="w-full cursor-not-allowed rounded-md border border-base-300 bg-base-200 px-3 py-2 text-xs text-base-content/80" />
    </div>
    @if ($hint)
        <p class="mt-1 mb-0 text-xs text-base-content/50">{{ $hint }}</p>
    @endif
</div>
