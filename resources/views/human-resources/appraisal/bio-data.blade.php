{{-- The officer, as the Registry holds them — filled in from the staff
     record, and correctable here, since what the Registry holds is sometimes
     wrong and sending the reporting officer away to put it right loses their
     place in the report. --}}

<form method="POST" action="{{ route('hr-appraisal.bio-data') }}" class="py-3">
    @csrf
    <input type="hidden" name="staff" value="{{ $staff->ulid }}" />
    <input type="hidden" name="section" value="{{ $section }}" />

    <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
        <x-registry.field name="surname" label="Surname" required :value="$staff->surname" />
        <x-registry.field name="firstname" label="First Name" required :value="$staff->firstname" />
        <x-registry.field name="othernames" label="Middle Name" :value="$staff->othernames"
                          placeholder="Leave blank where there is none" />
    </div>

    <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
        <x-registry.field name="date_of_birth" label="Date of Birth" type="date" required
                          :value="$staff->date_of_birth" />
        <x-registry.field name="state_of_origin" label="State of Origin" required
                          :value="$staff->state_of_origin" :options="$states" />

        <div class="w-full">
            <label class="font-sans text-sm font-medium text-base-content">
                Grade Level / Step<span class="text-error">*</span>
            </label>
            <div class="mt-1 flex gap-2">
                {{-- Two columns on the record, so two fields here. --}}
                <input name="grade_level" type="number" step="1" required
                       min="{{ \App\Models\BasicInformation::GRADE_LEVELS[0] }}"
                       max="{{ \App\Models\BasicInformation::GRADE_LEVELS[1] }}"
                       value="{{ old('grade_level', $staff->grade_level) }}"
                       class="w-full rounded-md border border-base-300 bg-white px-3 py-2 text-xs text-base-content/80 transition duration-300 ease focus:border-primary focus:outline-none hover:border-primary/50" />
                <input name="step" type="number" step="1" min="1" max="15" required
                       value="{{ old('step', $staff->step) }}"
                       class="w-full rounded-md border border-base-300 bg-white px-3 py-2 text-xs text-base-content/80 transition duration-300 ease focus:border-primary focus:outline-none hover:border-primary/50" />
            </div>
            <p class="mt-1 mb-0 text-xs text-base-content/50">Grade level, then step.</p>
            @error('grade_level') <p class="mt-1 mb-0 text-xs text-error">{{ $message }}</p> @enderror
            @error('step') <p class="mt-1 mb-0 text-xs text-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
        {{-- Deployment follows from the officer's postings, so it is shown
             rather than asked for: it is changed under Posting & Deployment,
             where the move itself is recorded. --}}
        <x-appraisal.field label="Deployment" :value="$deployment"
                           hint="Follows from the officer's postings. Change it under Registry → Posting & Deployment." />

        {{-- A department is a thing an officer at the state office belongs
             to, and a unit a desk within it; out in a local government there
             is neither to name, and the deployment beside it has already
             said where they are. --}}
        @if ($atHeadquarters)
            <x-registry.field name="department" label="Department" required
                              :value="$staff->department" :options="$departments" />

            {{-- A unit belongs to a department, so only that department's
                 are offered. --}}
            <div class="w-full">
                <label for="unit" class="font-sans text-sm font-medium text-base-content">Unit</label>
                <div class="relative mt-1">
                    <select id="unit" name="unit" data-selected="{{ old('unit', $staff->unit) }}"
                            data-department="{{ $staff->department }}"
                            class="w-full cursor-pointer rounded-md border border-base-300 bg-white px-3 py-2 text-xs text-base-content/80 transition duration-300 ease focus:border-primary focus:outline-none hover:border-primary/50 disabled:cursor-not-allowed disabled:bg-base-200">
                        <option value="">Select a department first</option>
                    </select>
                </div>
                <p class="mt-1 mb-0 text-xs text-base-content/50">
                    The desk the officer holds. Set under Administration &rarr; Departments &amp; Units.
                </p>
                @error('unit')
                    <p class="mt-1 mb-0 text-xs text-error">{{ $message }}</p>
                @enderror
            </div>
        @endif
    </div>

    <div class="flex flex-wrap items-center justify-end gap-2 border-t border-base-200 py-3">
        @if ($continueLabel !== null)
            <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">{{ $continueLabel }}</button>
        @else
            <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">Save Bio-data</button>
        @endif
    </div>
</form>

<script>
    // A unit belongs to a department, so the select is filled from the
    // department beside it — or, for an officer posted out and so not asked
    // for one, from the department their record already holds.
    (function () {
        const unitsByDepartment = @json(\App\Models\Department::unitsByDepartment());

        const department = document.getElementById('department');
        const unit = document.getElementById('unit');

        if (! unit) {
            return;
        }

        const chosen = () => department ? department.value : unit.dataset.department;

        function fillUnits(preselect) {
            const units = unitsByDepartment[chosen()] || [];

            unit.innerHTML = '';
            unit.disabled = units.length === 0;

            if (units.length === 0) {
                unit.add(new Option(chosen()
                    ? 'That department has no units yet'
                    : 'Select a department first', ''));
                return;
            }

            unit.add(new Option('Select Option', ''));

            units.forEach(function (name) {
                unit.add(new Option(name, name, false, name === preselect));
            });
        }

        department?.addEventListener('change', () => fillUnits(null));

        fillUnits(unit.dataset.selected);
    })();
</script>
