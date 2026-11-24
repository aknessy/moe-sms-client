<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Registry Officer Delegates">
            <x-registry.card title="Designate a Registry Officer"
                             description="An officer of {{ $department ?: 'your department' }}, or posted to it, who works alongside you. They can do what you can, but cannot sign or approve on your behalf.">
                <form method="POST" action="{{ route('gap-delegates.store') }}"
                      class="flex flex-col gap-4 lg:flex-row lg:items-end">
                    @csrf
                    <x-registry.picker name="basic_information_ulid" label="Staff" :options="$staffOptions" :value="old('basic_information_ulid')" required />
                    <div class="flex flex-wrap items-center gap-2 pb-0.5">
                        <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">Designate</button>
                    </div>
                </form>
                @error('basic_information_ulid')
                    <p class="mt-2 mb-0 text-xs text-error">{{ $message }}</p>
                @enderror
                @if ($staffOptions->isEmpty())
                    <p class="mt-3 mb-0 text-xs text-base-content/50">
                        There is nobody to designate: only officers with an account, in your department or posted to it, are offered.
                    </p>
                @endif
            </x-registry.card>

            <x-registry.card title="Designated Officers" headerClass="bg-[#2E7D32]" :count="$delegates->count()">
                @forelse ($delegates as $delegate)
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-3 rounded-md border border-base-300 p-3">
                        <div>
                            <p class="m-0 text-sm font-medium text-base-content">
                                {{ $delegate->staff?->surname }}, {{ $delegate->staff?->firstname }}
                                <span class="text-base-content/50">({{ $delegate->staff?->staff_number }})</span>
                            </p>
                            <p class="m-0 text-xs text-base-content/50">Designated {{ $delegate->created_at->format('d M Y') }}</p>
                        </div>
                        <form method="POST" action="{{ route('gap-delegates.revoke', $delegate->basic_information_ulid) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-cta-outline px-4">Withdraw</button>
                        </form>
                    </div>
                @empty
                    <p class="py-4 text-center text-sm text-base-content/60 m-0">Nobody is designated yet.</p>
                @endforelse
            </x-registry.card>
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
