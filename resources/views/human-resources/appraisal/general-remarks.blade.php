{{-- Anything the sections before it did not already ask after one at a
     time: a particular strength or weakness worth drawing attention to, in
     the reporting officer's own words.

     The writing is the reporting officer's to do — the Electoral Officer
     of the LGA the officer is deployed to, or the head of the department
     they belong to — so anyone else reads what is on record rather than
     filling the form in. --}}

@if ($mayComment)
    <form method="POST" action="{{ route('hr-appraisal.general-remarks') }}" class="py-3"
          data-general-remarks>
        @csrf
        <input type="hidden" name="staff" value="{{ $staff->ulid }}" />
        <input type="hidden" name="section" value="{{ $section }}" />

        <div class="mb-3 w-full">
            <label for="remarks" class="font-sans text-sm font-medium text-base-content">
                Provide any additional information here drawing attention to any
                particular strength or weakness.<span class="text-error">*</span>
            </label>
            <textarea id="remarks" name="remarks" rows="6" required
                      placeholder="Any particular strength or weakness worth drawing attention to"
                      class="mt-1 w-full resize-none rounded-md border border-base-300 bg-white px-3 py-2 text-xs text-base-content/80 transition duration-300 ease focus:border-primary focus:outline-none hover:border-primary/50">{{ old('remarks', $generalRemark?->remarks) }}</textarea>
            @error('remarks')
                <p class="mt-1 mb-0 text-xs text-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-base-200 py-3">
            <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">
                {{ $continueLabel ?? 'Save General Remarks' }}
            </button>
        </div>
    </form>
@else
    <div class="py-3">
        <div class="mb-3 w-full">
            <span class="font-sans text-sm font-medium text-base-content">General Remarks</span>
            <p class="mt-0.5 mb-0 font-sans text-sm text-base-content/80">
                {{ $generalRemark?->remarks ?? 'Nothing has been written here yet.' }}
            </p>
        </div>

        <p class="mt-0.5 mb-0 text-xs text-base-content/40">
            General Remarks is written by the Electoral Officer of the local
            government the officer is deployed to, the head of the officer's
            department, or an administrator.
        </p>

        @if ($continueLabel)
            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-base-200 py-3">
                <a href="{{ route('hr-appraisal', ['section' => $nextSection, 'staff' => $staff->ulid]) }}"
                   class="btn btn-sm btn-cta px-4 shadow-md">{{ $continueLabel }}</a>
            </div>
        @endif
    </div>
@endif
