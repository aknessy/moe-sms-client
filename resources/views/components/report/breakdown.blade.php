@props([
    'title',
    'description' => '',
    'rows',                      // [label => count]
    'heading' => 'Group',
    'empty' => 'Nothing to show.',
])

@php
    $total = collect($rows)->sum();
    $widest = collect($rows)->max() ?: 1;
@endphp

<x-registry.card :title="$title" :description="$description">
    @if (collect($rows)->isEmpty())
        <p class="py-6 text-center text-sm text-base-content/60">{{ $empty }}</p>
    @else
        <table class="w-full text-left text-xs">
            <thead>
                <tr class="border-b border-base-300 text-base-content/60">
                    <th class="px-3 py-2 font-medium">{{ $heading }}</th>
                    <th class="px-3 py-2 font-medium text-right">Staff</th>
                    <th class="px-3 py-2 font-medium text-right">Share</th>
                    <th class="px-3 py-2 font-medium w-1/3">&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $label => $count)
                    <tr class="border-b border-base-200 hover:bg-base-200/60">
                        <td class="px-3 py-2">{{ $label }}</td>
                        <td class="px-3 py-2 text-right font-medium text-base-content">{{ $count }}</td>
                        <td class="px-3 py-2 text-right text-base-content/60">
                            {{ $total ? round($count / $total * 100) : 0 }}%
                        </td>
                        <td class="px-3 py-2">
                            {{-- Bar drawn against the largest group, so the
                                 shape of the distribution is readable at a glance. --}}
                            <div class="h-2 w-full rounded-full bg-base-200">
                                <div class="h-2 rounded-full bg-[#2E7D32]"
                                     style="width: {{ round($count / $widest * 100) }}%"></div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</x-registry.card>
