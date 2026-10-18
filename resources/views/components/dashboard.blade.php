<x-layout title="{{ $pageTitle }}">
    <div class="flex flex-1 h-screen min-h-full">
        <x-navigation.sidebar :segments="$segments ?? null" />
        <div class="flex flex-col flex-1 min-w-0 overflow-y-auto">
            <x-navigation.navbar />
            <div class="flex-1 bg-base-200 p-4">
                {{ $slot }}
            </div>
        </div>
    </div>
</x-layout>
