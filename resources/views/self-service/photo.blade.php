<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Update Photo" crumb="My Records">
            <x-registry.card title="Passport Photograph"
                             description="A JPEG or PNG, at most 2MB. The one you upload replaces whatever is on file.">

                <div class="flex flex-col gap-6 py-4 sm:flex-row sm:items-start">
                    <div class="shrink-0 text-center">
                        <div class="h-40 w-40 overflow-hidden rounded-md border border-base-300 bg-base-200">
                            @if ($staff->has_photo)
                                <img src="{{ route('my-photo.show') }}" alt="Your passport photograph"
                                     class="h-full w-full object-cover" />
                            @else
                                <div class="flex h-full w-full items-center justify-center text-xs text-base-content/40">
                                    Nothing on file
                                </div>
                            @endif
                        </div>
                        @if ($staff->has_photo)
                            <p class="mt-2 mb-0 text-xs text-base-content/50">{{ $staff->photo_name }}</p>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('my-photo.store') }}"
                          enctype="multipart/form-data" class="w-full">
                        @csrf

                        <div class="w-full">
                            <label for="photo" class="font-sans text-sm font-medium text-base-content">
                                Choose a photograph<span class="text-error">*</span>
                            </label>
                            <div class="relative mt-1">
                                <input id="photo" name="photo" type="file" accept="image/jpeg,image/png" required
                                       class="w-full cursor-pointer rounded-md border border-base-300 bg-white px-3 py-2 text-xs text-base-content/80 file:mr-3 file:rounded file:border-0 file:bg-[#2E7D32] file:px-3 file:py-1 file:text-xs file:text-white" />
                            </div>
                            @error('photo')
                                <p class="mt-1 text-xs text-error">{{ $message }}</p>
                            @enderror
                            <p class="mt-2 mb-0 text-xs text-base-content/50">
                                A head-and-shoulders photograph against a plain background, as on a passport.
                            </p>
                        </div>

                        <div class="py-4 flex flex-wrap items-center justify-end gap-2">
                            <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">Upload Photograph</button>
                        </div>
                    </form>
                </div>
            </x-registry.card>
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
