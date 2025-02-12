<x-layout title="{{ $pageTitle }}">
    <div class="flex flex-1 h-screen min-h-full">
        <x-navigation.sidebar :segments="$segments" />
        <div class="flex flex-col flex-1 overflow-y-auto">
            <x-navigation.navbar />
            <div class="p-4">
                {{ $slot }}
            </div>
        </div>
    </div>
</x-layout>
