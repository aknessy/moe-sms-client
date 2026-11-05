<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Settings" crumb="Administration">

            <x-registry.card title="Organisation Details"
                             description="These head every letter the system raises — a leave application, and whatever follows it.">
                <form method="POST" action="{{ route('config.organisation') }}">
                    @csrf

                    <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                        <x-registry.field name="organisation_name" label="Name" required
                                          :value="$organisation['organisation.name']"
                                          placeholder="Independent National Electoral Commission" />
                        <x-registry.field name="organisation_command" label="Command / Office"
                                          :value="$organisation['organisation.command']"
                                          placeholder="e.g. Cross River State Office" />
                    </div>

                    <div class="mb-3 w-full">
                        <x-registry.field name="organisation_address" label="Address" rows="3"
                                          :value="$organisation['organisation.address']"
                                          placeholder="The postal address, as it should print on a letterhead" />
                    </div>

                    <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                        <x-registry.field name="organisation_telephone" label="Telephone"
                                          :value="$organisation['organisation.telephone']"
                                          placeholder="e.g. 08030000000" />
                        <x-registry.field name="organisation_email" label="Email" type="email"
                                          :value="$organisation['organisation.email']"
                                          placeholder="e.g. crossriver@inecnigeria.org" />
                    </div>

                    <div class="mb-3 w-full">
                        <x-registry.field name="organisation_commissioner_title"
                                          label="Applications are addressed to" required
                                          :value="$organisation['organisation.commissioner_title']"
                                          placeholder="The Resident Electoral Commissioner" />
                        <p class="mt-1 mb-0 text-xs text-base-content/50">
                            The desk a staff application is finally addressed to, after the
                            Administrative Secretary and the officer's own head of department.
                        </p>
                    </div>

                    <div class="py-3 flex flex-wrap items-center justify-end gap-2">
                        <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">Save Details</button>
                    </div>
                </form>
            </x-registry.card>

            <x-registry.card title="Promotion"
                             description="How far down the grade levels a promotion exercise reaches.">
                <form method="POST" action="{{ route('config.rules') }}">
                    @csrf

                    <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                        <div class="w-full lg:max-w-xs">
                            <label for="promotion_lowest_grade_level"
                                   class="font-sans text-sm font-medium text-base-content">
                                Lowest Grade Level Considered<span class="text-error">*</span>
                            </label>
                            <div class="relative mt-1">
                                <input id="promotion_lowest_grade_level" name="promotion_lowest_grade_level"
                                       type="number" step="1" min="1" max="{{ $topGradeLevel }}" required
                                       value="{{ old('promotion_lowest_grade_level', $lowestGradeLevel) }}"
                                       class="w-full bg-white text-base-content/80 text-xs border border-base-300 rounded-md px-3 py-2 transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" />
                            </div>
                            @error('promotion_lowest_grade_level')
                                <p class="mt-1 mb-0 text-xs text-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="w-full lg:flex-1">
                            <p class="mt-6 mb-0 text-xs text-base-content/50">
                                An officer below this grade level is left off the promotion brief.
                                GL {{ $topGradeLevel }} cannot be promoted at all, so setting this
                                to {{ $topGradeLevel }} would leave nobody to consider.
                            </p>
                        </div>
                    </div>

                    <div class="py-3 flex flex-wrap items-center justify-end gap-2">
                        <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">Save Promotion Rules</button>
                    </div>
                </form>
            </x-registry.card>

        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
