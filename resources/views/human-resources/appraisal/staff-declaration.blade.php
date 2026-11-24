{{-- The officer's own certification that they have seen the contents of
     this report and that the reporting officer has discussed it with them,
     with whatever comments they have to make on it.

     The declaration is the officer's own to make, not the reporting
     officer's — so even the Electoral Officer or head of department who may
     comment on the sections before it only reads what is on record here. An
     administrator may still make it on the officer's behalf. --}}

@if ($mayDeclare)
    <form method="POST" action="{{ route('hr-appraisal.staff-declaration') }}" class="py-3"
          data-staff-declaration>
        @csrf
        <input type="hidden" name="staff" value="{{ $staff->ulid }}" />
        <input type="hidden" name="section" value="{{ $section }}" />

        <p class="mb-3 font-sans text-sm text-base-content/80">
            I certify that I have seen the contents of this report and that
            the reporting officer has discussed them with me. I have the
            following comments to make (if no comments, indicate so
            hereunder):
        </p>

        <div class="mb-3 w-full">
            <label for="comments" class="sr-only">Comments</label>
            <textarea id="comments" name="comments" rows="6" required
                      placeholder="Your comments, or state that you have none"
                      class="w-full resize-none rounded-md border border-base-300 bg-white px-3 py-2 text-xs text-base-content/80 transition duration-300 ease focus:border-primary focus:outline-none hover:border-primary/50">{{ old('comments', $staffDeclaration?->comments) }}</textarea>
            @error('comments')
                <p class="mt-1 mb-0 text-xs text-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-3 grid grid-cols-1 gap-3 md:grid-cols-2">
            <div class="w-full">
                <label for="declared_on" class="mb-1 block text-xs font-semibold text-base-content/70">Date*</label>
                <input id="declared_on" name="declared_on" type="date" required
                       value="{{ old('declared_on', $staffDeclaration?->declared_on?->toDateString() ?? now()->toDateString()) }}"
                       class="w-full rounded-md border border-base-300 bg-white px-3 py-2 text-xs text-base-content/80 transition duration-300 ease focus:border-primary focus:outline-none hover:border-primary/50" />
                @error('declared_on')
                    <p class="mt-1 mb-0 text-xs text-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="w-full">
                <label for="signature" class="mb-1 block text-xs font-semibold text-base-content/70">Signature*</label>
                <input id="signature" name="signature" type="text" required
                       value="{{ old('signature', $staffDeclaration?->signature) }}"
                       placeholder="Type your name to sign"
                       class="w-full rounded-md border border-base-300 bg-white px-3 py-2 text-xs text-base-content/80 transition duration-300 ease focus:border-primary focus:outline-none hover:border-primary/50" />
                @error('signature')
                    <p class="mt-1 mb-0 text-xs text-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        @if ($staffDeclaration)
            <p class="mt-0.5 mb-3 text-xs text-base-content/40">
                Last declared on {{ $staffDeclaration->updated_at->format('d M Y') }}.
            </p>
        @endif

        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-base-200 py-3">
            <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">
                {{ $continueLabel ?? 'Save Staff Declaration' }}
            </button>
        </div>
    </form>
@else
    <div class="py-3">
        <p class="mb-3 font-sans text-sm text-base-content/80">
            I certify that I have seen the contents of this report and that
            the reporting officer has discussed them with me. I have the
            following comments to make (if no comments, indicate so
            hereunder):
        </p>

        <div class="mb-3 w-full">
            <p class="mt-0.5 mb-0 font-sans text-sm text-base-content/80">
                {{ $staffDeclaration?->comments ?? 'Nothing has been declared here yet.' }}
            </p>
        </div>

        @if ($staffDeclaration?->signature)
            <dl class="mb-3 grid grid-cols-1 gap-2 text-sm text-base-content/80 md:grid-cols-2">
                <div><dt class="text-xs font-semibold text-base-content/60">Date</dt><dd class="m-0">{{ $staffDeclaration->declared_on?->format('d M Y') }}</dd></div>
                <div><dt class="text-xs font-semibold text-base-content/60">Signature</dt><dd class="m-0">{{ $staffDeclaration->signature }}</dd></div>
            </dl>
        @endif

        @if ($staffDeclaration)
            <p class="mt-0.5 mb-3 text-xs text-base-content/40">
                Declared on {{ $staffDeclaration->updated_at->format('d M Y') }}.
            </p>
        @endif

        <p class="mt-0.5 mb-0 text-xs text-base-content/40">
            The Staff Declaration is made by the officer themselves, or an
            administrator.
        </p>

        @if ($continueLabel)
            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-base-200 py-3">
                <a href="{{ route('hr-appraisal', ['section' => $nextSection, 'staff' => $staff->ulid]) }}"
                   class="btn btn-sm btn-cta px-4 shadow-md">{{ $continueLabel }}</a>
            </div>
        @endif
    </div>
@endif
