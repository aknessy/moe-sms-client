{{-- What the officer did, quarter by quarter, and what the reporting officer
     made of each of it.

     Three years of three quarters is nine blocks, which is too many to scroll
     through, so the years are tabs and the whole of it is one form: moving
     between years must not lose what has been written in another. --}}

@php
    use App\Models\SelfReportActivity;

    $quarters = SelfReportActivity::QUARTERS;
    $least = SelfReportActivity::LEAST_ACTIVITIES;

    // The year the form opens on: the first one anything was rejected for,
    // or failing that the present one, so the officer lands where the work is.
    $faulted = collect($errors->keys())
        ->first(fn ($key) => str_starts_with($key, 'activity.'));

    $openYear = $faulted
        ? (int) explode('.', $faulted)[1]
        : (in_array(now()->year, $reportingYears, true) ? now()->year : end($reportingYears));
@endphp

<form method="POST" action="{{ route('hr-appraisal.self-reporting') }}" class="py-3"
      data-self-reporting>
    @csrf
    <input type="hidden" name="staff" value="{{ $staff->ulid }}" />
    <input type="hidden" name="section" value="{{ $section }}" />

    <div class="mb-3">
        <p class="m-0 font-sans text-sm font-medium text-base-content">
            Activities Taken Part In
        </p>
        <p class="mt-0.5 mb-0 text-xs text-base-content/50">
            Three years, each over its first three quarters. A quarter written in
            needs at least {{ $least }} activities; one not reached yet is left
            empty. The comment beside each is the reporting officer's.
        </p>
    </div>

    {{-- The years, as tabs. --}}
    <div class="mb-3 flex flex-wrap gap-1 border-b border-base-300" role="tablist">
        @foreach ($reportingYears as $year)
            <button type="button" data-year-tab="{{ $year }}" role="tab"
                    class="-mb-px border-b-2 px-4 py-2 font-sans text-xs transition
                           {{ $year === $openYear
                              ? 'border-primary font-semibold text-primary'
                              : 'border-transparent text-base-content/60 hover:text-base-content' }}">
                {{ $year }}
            </button>
        @endforeach
    </div>

    @foreach ($reportingYears as $year)
        <div data-year-pane="{{ $year }}" class="{{ $year === $openYear ? '' : 'hidden' }}">
            @foreach ($quarters as $quarter => $quarterLabel)
                @php
                    // What to show: whatever was just typed if the form came
                    // back with an error, and what is on file otherwise.
                    $typed = old("activity.{$year}.{$quarter}");

                    $held = $typed !== null
                        ? collect($typed)->map(fn ($activity, $row) => [
                              'activity' => $activity,
                              'comment' => old("comment.{$year}.{$quarter}.{$row}"),
                          ])->values()
                        : SelfReportActivity::inQuarter($selfReport, $year, $quarter)
                              ->map(fn ($entry) => [
                                  'activity' => $entry->activity,
                                  'comment' => $entry->comment,
                              ])->values();

                    // Always the four rows the quarter is answered with, and
                    // more where more were written.
                    $rows = $held->pad(max($least, $held->count()), ['activity' => null, 'comment' => null]);
                @endphp

                <div class="mb-3 rounded-md border border-base-300"
                     data-quarter="{{ $year }}.{{ $quarter }}">
                    <div class="flex items-center justify-between border-b border-base-200 px-4 py-2">
                        <p class="m-0 font-sans text-xs font-semibold uppercase tracking-wide text-base-content/70">
                            {{ $quarterLabel }} &middot; {{ $year }}
                        </p>
                        <span class="text-xs text-base-content/40" data-counted>
                            {{ $held->filter(fn ($row) => filled($row['activity']))->count() }} described
                        </span>
                    </div>

                    @error("activity.{$year}.{$quarter}")
                        <p class="mx-4 mt-3 mb-0 rounded border border-error/40 bg-error/5 px-3 py-2 text-xs text-error">
                            {{ $message }}
                        </p>
                    @enderror

                    <div class="flex flex-col gap-3 px-4 py-3" data-activities>
                        @foreach ($rows as $index => $row)
                            <div class="relative rounded-md border border-base-200 bg-base-100/40 px-3 py-3"
                                 data-activity-row>
                                <div class="flex flex-col gap-4 lg:flex-row">
                                    <div class="w-full">
                                        <label class="font-sans text-xs font-medium text-base-content">
                                            Activity <span data-activity-number>{{ $index + 1 }}</span>
                                        </label>
                                        <textarea name="activity[{{ $year }}][{{ $quarter }}][]" rows="3"
                                                  placeholder="What the officer took part in, and what they did in it"
                                                  class="mt-1 w-full resize-none rounded-md border border-base-300 bg-white px-3 py-2 text-xs text-base-content/80 transition duration-300 ease focus:border-primary focus:outline-none hover:border-primary/50">{{ $row['activity'] }}</textarea>
                                    </div>

                                    <div class="w-full">
                                        <label class="font-sans text-xs font-medium text-base-content">
                                            Reporting Officer's Comments
                                        </label>
                                        <textarea name="comment[{{ $year }}][{{ $quarter }}][]" rows="3"
                                                  placeholder="Left empty until the reporting officer has answered it"
                                                  class="mt-1 w-full resize-none rounded-md border border-base-300 bg-white px-3 py-2 text-xs text-base-content/80 transition duration-300 ease focus:border-primary focus:outline-none hover:border-primary/50">{{ $row['comment'] }}</textarea>
                                    </div>
                                </div>

                                <button type="button" data-remove-activity
                                        class="absolute -right-2 -top-2 hidden h-6 w-6 items-center justify-center rounded-full border border-error bg-error text-xs text-error-content shadow"
                                        title="Remove this activity">&times;</button>
                            </div>
                        @endforeach
                    </div>

                    <div class="px-4 pb-3">
                        <button type="button" data-add-activity class="btn btn-sm btn-cta-outline px-4">
                            Add Activity
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach

    <div class="flex flex-wrap items-center justify-end gap-2 border-t border-base-200 py-3">
        <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">
            {{ $continueLabel ?? 'Save Self Reporting' }}
        </button>
    </div>
</form>

<script>
    (function () {
        const form = document.querySelector('[data-self-reporting]');

        if (! form) {
            return;
        }

        /* ------------------------------------------------------ the years */

        // Every year stays in the form; only one is on show. Switching must
        // not lose what was written in another, so the panes are hidden
        // rather than replaced.
        form.querySelectorAll('[data-year-tab]').forEach(function (tab) {
            tab.addEventListener('click', function () {
                const year = tab.dataset.yearTab;

                form.querySelectorAll('[data-year-pane]').forEach(function (pane) {
                    pane.classList.toggle('hidden', pane.dataset.yearPane !== year);
                });

                form.querySelectorAll('[data-year-tab]').forEach(function (other) {
                    const on = other === tab;

                    other.classList.toggle('border-primary', on);
                    other.classList.toggle('font-semibold', on);
                    other.classList.toggle('text-primary', on);
                    other.classList.toggle('border-transparent', ! on);
                    other.classList.toggle('text-base-content/60', ! on);
                });
            });
        });

        /* -------------------------------------------------- the activities */

        // Another activity is another row of the same shape, so the first one
        // in the quarter is the pattern for the rest. The rows are parallel
        // arrays that line up by position, so a clone needs no renumbering.
        function refresh(block) {
            const rows = block.querySelectorAll('[data-activity-row]');
            const counted = block.closest('[data-quarter]').querySelector('[data-counted]');

            rows.forEach(function (row, at) {
                const number = row.querySelector('[data-activity-number]');

                if (number) {
                    number.textContent = at + 1;
                }

                const remove = row.querySelector('[data-remove-activity]');

                if (remove) {
                    // A quarter is answered with four, so the first four stay.
                    const spare = rows.length > {{ $least }};

                    remove.classList.toggle('hidden', ! spare);
                    remove.classList.toggle('flex', spare);
                }
            });

            const described = Array.from(rows).filter(function (row) {
                return row.querySelector('textarea').value.trim() !== '';
            }).length;

            counted.textContent = described + ' described';
        }

        form.querySelectorAll('[data-add-activity]').forEach(function (add) {
            add.addEventListener('click', function () {
                const block = add.closest('[data-quarter]').querySelector('[data-activities]');
                const row = block.querySelector('[data-activity-row]').cloneNode(true);

                // A clone carries whatever was typed into the row it came
                // from; each one is a different activity, so it starts empty.
                row.querySelectorAll('textarea').forEach(field => field.value = '');

                block.appendChild(row);
                refresh(block);
            });
        });

        form.addEventListener('click', function (event) {
            if (! event.target.closest('[data-remove-activity]')) {
                return;
            }

            const block = event.target.closest('[data-activities]');

            event.target.closest('[data-activity-row]').remove();
            refresh(block);
        });

        form.addEventListener('input', function (event) {
            if (event.target.matches('textarea[name^="activity"]')) {
                refresh(event.target.closest('[data-activities]'));
            }
        });

        form.querySelectorAll('[data-activities]').forEach(refresh);
    })();
</script>
