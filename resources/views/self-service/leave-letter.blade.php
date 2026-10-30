<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Leave Application" crumb="My Records">

            <div class="mb-4 flex flex-wrap items-center justify-between gap-3 print:hidden">
                <div class="flex items-center gap-3">
                    <a href="{{ route('my-leave') }}" class="btn btn-sm btn-cta-outline px-4">
                        &larr; Back to my applications
                    </a>
                    <x-status-pill :status="$application->status"
                                   :label="\App\Models\LeaveApplication::STATUSES[$application->status] ?? 'Pending'" />
                </div>
                <button type="button" onclick="window.print()" class="btn btn-sm btn-cta px-4 shadow-md">
                    Print
                </button>
            </div>

            <x-leave-letter :letter="$letter" />
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
