<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Leave Application" crumb="Human Resources">

            <div class="mb-4 flex flex-wrap items-center justify-between gap-3 print:hidden">
                <div class="flex items-center gap-3">
                    <a href="{{ route('hr-leave') }}" class="btn btn-sm btn-cta-outline px-4">
                        &larr; Back to applications
                    </a>
                    <x-status-pill :status="$application->status"
                                   :label="$statuses[$application->status] ?? 'Pending'" />
                    @if (filled($application->remarks))
                        <span class="text-xs text-base-content/60">{{ $application->remarks }}</span>
                    @endif
                </div>
                <button type="button" onclick="window.print()" class="btn btn-sm btn-cta px-4 shadow-md">
                    Print
                </button>
            </div>

            <x-leave-letter :letter="$letter" />

            {{-- Acting on it is still to come: who may approve, and what
                 happens to the officer's record when they do, has not been
                 settled. The letter is what the reviewer needs to read in the
                 meantime. --}}
            <div class="mx-auto mt-4 w-full max-w-3xl print:hidden">
                <div class="rounded-md border border-base-300 bg-base-200 px-4 py-3 text-xs text-base-content/60">
                    Approving and declining from this page is not built yet.
                </div>
            </div>
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
