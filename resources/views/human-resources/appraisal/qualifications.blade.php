{{-- What the officer has qualified in, captured here whether or not any are
     on record. The rows are parallel arrays that line up by position, which
     is what lets one be cloned without renumbering anything. --}}

<form method="POST" action="{{ route('hr-appraisal.qualifications') }}" class="py-3">
    @csrf
    <input type="hidden" name="staff" value="{{ $staff->ulid }}" />
    <input type="hidden" name="section" value="{{ $section }}" />

    <div class="mb-3 w-full">
        <div class="mb-2">
            <p class="m-0 font-sans text-sm font-medium text-base-content">
                Qualifications (Educational / Professional / Technical)
            </p>
            <p class="mt-0.5 mb-0 text-xs text-base-content/50">
                With the year each was obtained. A row with no qualification named is ignored.
            </p>
        </div>

        <div class="flex flex-col gap-3" data-qualifications>
            {{-- At least one row to write in, even where nothing is on file. --}}
            @foreach ($qualifications->isEmpty() ? [null] : $qualifications as $qualification)
                <div class="relative rounded-md border border-base-300 px-4 py-3" data-qualification-row>
                    <div class="flex flex-col gap-4 lg:flex-row">
                        <x-registry.field name="qual_type[]" label="Type"
                                          :value="$qualification['type'] ?? null"
                                          :options="collect(\App\Support\Appraisal::QUALIFICATION_TYPES)
                                                      ->mapWithKeys(fn ($t) => [$t => $t])->all()" />
                        <x-registry.field name="qual_name[]" label="Qualification"
                                          :value="$qualification['qualification'] ?? null"
                                          placeholder="e.g. B.Sc Political Science" />
                        <x-registry.field name="qual_institution[]" label="Institution"
                                          :value="$qualification['institution'] ?? null"
                                          placeholder="e.g. University of Calabar" />
                        <x-registry.field name="qual_year[]" label="Year Obtained" type="number"
                                          :value="$qualification['year'] ?? null"
                                          placeholder="e.g. 2007" />
                    </div>

                    {{-- Carried through untouched, so editing a qualification
                         here does not throw away what the wizard recorded
                         about the schooling behind it. --}}
                    <input type="hidden" name="qual_start[]" value="{{ $qualification['start_date'] ?? '' }}" />
                    <input type="hidden" name="qual_end[]" value="{{ $qualification['end_date'] ?? '' }}" />

                    <button type="button" data-remove-qualification
                            class="absolute -right-2 -top-2 hidden h-6 w-6 items-center justify-center rounded-full border border-error bg-error text-xs text-error-content shadow"
                            title="Remove this qualification">&times;</button>
                </div>
            @endforeach
        </div>

        <div class="py-3">
            <button type="button" data-add-qualification class="btn btn-sm btn-cta-outline px-4">
                Add Qualification
            </button>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-end gap-2 border-t border-base-200 py-3">
        <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">
            {{ $continueLabel ?? 'Save Qualifications' }}
        </button>
    </div>
</form>

<script>
    // Another qualification is another row of the same shape, so the first
    // one is the pattern for the rest. The rows are parallel arrays that line
    // up by position, so a clone needs no renumbering.
    (function () {
        const rows = document.querySelector('[data-qualifications]');
        const add = document.querySelector('[data-add-qualification]');

        if (! rows || ! add) {
            return;
        }

        // The first row stays: there is always somewhere to write.
        function showRemoveButtons() {
            const all = rows.querySelectorAll('[data-qualification-row]');

            all.forEach(function (row) {
                const button = row.querySelector('[data-remove-qualification]');

                if (button) {
                    button.classList.toggle('hidden', all.length === 1);
                    button.classList.toggle('flex', all.length > 1);
                }
            });
        }

        add.addEventListener('click', function () {
            const row = rows.querySelector('[data-qualification-row]').cloneNode(true);

            // A clone carries whatever was typed into the row it came from;
            // each one is a different qualification, so it starts empty.
            row.querySelectorAll('input').forEach(input => input.value = '');
            row.querySelectorAll('select').forEach(select => select.selectedIndex = 0);

            // Labels are tied to ids, and a second row would steal the first
            // row's focus on click.
            row.querySelectorAll('[id]').forEach(el => el.removeAttribute('id'));
            row.querySelectorAll('label[for]').forEach(el => el.removeAttribute('for'));

            rows.appendChild(row);
            showRemoveButtons();
        });

        rows.addEventListener('click', function (event) {
            if (! event.target.closest('[data-remove-qualification]')) {
                return;
            }

            event.target.closest('[data-qualification-row]').remove();
            showRemoveButtons();
        });

        showRemoveButtons();
    })();
</script>
