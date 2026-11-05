<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Departments & Units" crumb="Administration">

            <x-registry.card title="Add a Department"
                             description="The staff wizard offers these and nothing else, so a new directorate has to be recorded here before anyone can be put in it.">
                <form method="POST" action="{{ route('config.departments') }}"
                      class="flex w-full flex-col gap-4 py-3 sm:flex-row sm:items-end">
                    @csrf

                    <div class="min-w-0 flex-1">
                        <x-registry.field name="department" label="Department" required
                                          placeholder="e.g. Department of Legal Services" />
                    </div>

                    <div class="min-w-0 sm:max-w-xs sm:flex-1">
                        <x-registry.field name="role" label="Headed By" :options="$hodRoles"
                                          placeholder="Nobody named" />
                    </div>

                    <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md shrink-0">Add Department</button>
                </form>
            </x-registry.card>

            <x-registry.card title="Add a Unit"
                             description="A desk inside a department. The same name may serve two departments; within one it is named once.">
                <form method="POST" action="{{ route('config.units') }}"
                      class="flex w-full flex-col gap-4 py-3 sm:flex-row sm:items-end">
                    @csrf

                    <div class="min-w-0 flex-1">
                        <x-registry.field name="department_id" label="Department" required
                                          :options="$departments->pluck('department', 'id')->all()" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <x-registry.field name="name" label="Unit" required
                                          placeholder="e.g. Voter Registration Unit" />
                    </div>

                    <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md shrink-0">Add Unit</button>
                </form>
            </x-registry.card>

            <x-registry.card title="Departments" :count="$departments->count()"
                             description="Who heads each, and the units inside it">
                <form method="POST" action="{{ route('config.department-heads') }}">
                    @csrf

                    <div class="flex flex-col gap-3 py-3">
                        @forelse ($departments as $department)
                            <div class="rounded-md border border-base-300 px-4 py-3">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0">
                                        <p class="m-0 font-sans text-sm font-semibold text-base-content">
                                            {{ $department->department }}
                                        </p>
                                        <p class="mt-0.5 mb-0 text-xs text-base-content/50">
                                            {{ $department->units->count() }}
                                            unit{{ $department->units->count() === 1 ? '' : 's' }}
                                        </p>
                                    </div>

                                    <div class="shrink-0">
                                        <label class="mb-1 block text-xs text-base-content/50">Headed by</label>
                                        <select name="heads[{{ $department->id }}]"
                                                class="w-full min-w-[14rem] cursor-pointer rounded-md border border-base-300 bg-white px-3 py-2 text-xs text-base-content/80 focus:border-primary focus:outline-none">
                                            <option value="">Nobody named</option>
                                            @foreach ($hodRoles as $value => $label)
                                                <option value="{{ $value }}" @selected($department->role === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                {{-- The desks inside it. --}}
                                <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-base-200 pt-3">
                                    @forelse ($department->units->sortBy('name') as $unit)
                                        <span class="inline-flex items-center gap-1 rounded-full border border-base-300 bg-base-200/60 py-1 pl-3 pr-1 text-xs text-base-content/80">
                                            {{ $unit->name }}
                                            {{-- Submits the enclosing form to a
                                                 different address, which is how
                                                 a button inside one form can do
                                                 something else entirely. --}}
                                            <button type="submit" formnovalidate
                                                    formaction="{{ route('config.units.remove', $unit) }}"
                                                    class="flex h-4 w-4 items-center justify-center rounded-full text-base-content/40 hover:bg-error hover:text-error-content"
                                                    title="Remove {{ $unit->name }}">&times;</button>
                                        </span>
                                    @empty
                                        <p class="m-0 text-xs text-base-content/40">
                                            No units yet. Add one above.
                                        </p>
                                    @endforelse
                                </div>
                            </div>
                        @empty
                            <p class="py-8 text-center text-sm text-base-content/50 m-0">
                                No departments yet. Add one above.
                            </p>
                        @endforelse
                    </div>

                    @if ($departments->isNotEmpty())
                        <div class="flex flex-wrap items-center justify-end gap-2 py-3">
                            <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">Save Heads</button>
                        </div>
                    @endif
                </form>
            </x-registry.card>
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
