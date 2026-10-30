@props(['status', 'label' => null])

@php
    // Pending reads as waiting, not as a problem; declined is the only one
    // that should catch the eye.
    $tones = [
        'approved' => 'border-[#2E7D32]/40 bg-[#2E7D32]/10 text-[#1B5E20]',
        'declined' => 'border-error/40 bg-error/10 text-error',
        'pending' => 'border-base-300 bg-base-200 text-base-content/70',
    ];
@endphp

<span class="inline-block rounded-full border px-2 py-0.5 text-xs font-medium {{ $tones[$status] ?? $tones['pending'] }}">
    {{ $label ?? ucfirst($status) }}
</span>
