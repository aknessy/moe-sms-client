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
             to; out in a local government there is none to name, and the
             deployment beside it has already said where they are. --}}
        @if ($atHeadquarters)
            <x-registry.field name="department" label="Department" required
                              :value="$staff->department" :options="$departments" />
        @endif

        <x-registry.field name="division" label="Unit" required :value="$staff->division"
                          placeholder="e.g. Voter Registration Unit" />
    </div>

    <div class="flex flex-wrap items-center justify-end gap-2 border-t border-base-200 py-3">
        @if ($continueLabel !== null)
            <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">{{ $continueLabel }}</button>
        @else
            <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">Save Bio-data</button>
        @endif
    </div>
</form>
