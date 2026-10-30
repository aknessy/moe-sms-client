<x-dashboard :pageTitle="$page_title">
    <x-slot name="slot">
        @php $account = auth()->user(); @endphp

        <x-registry.page title="Change Password" crumb="Profile">
            <div class="flex w-full flex-1 flex-col gap-y-4 lg:flex-row lg:gap-x-8 lg:gap-y-0">

                {{-- Who is signed in, so there is no doubt whose password is
                     about to change. --}}
                <div class="w-full flex flex-col lg:w-2/5 px-4 py-4 bg-base-100 border border-base-300 rounded-md">
                    <div class="flex flex-col items-center gap-y-2">
                        <div class="h-28 w-28 overflow-hidden rounded-full border border-base-300 bg-base-200">
                            @if ($account?->staff?->has_photo)
                                <img src="{{ route('my-photo.show') }}" alt="Passport photograph"
                                     class="h-full w-full object-cover" />
                            @else
                                <div class="flex h-full w-full items-center justify-center text-xs text-base-content/40">
                                    No photo
                                </div>
                            @endif
                        </div>
                        <h3 class="m-0 font-sans text-lg font-bold">{{ $account?->full_name ?: $account?->username }}</h3>
                        <h4 class="m-0 text-sm font-normal text-base-content/70">{{ $account?->department ?: $account?->role }}</h4>
                        <h5 class="m-0 text-sm font-normal text-base-content">{{ $account?->email }}</h5>
                    </div>

                    <dl class="mt-5 grid grid-cols-1 gap-y-2 border-t border-base-300 pt-4">
                        <div class="flex items-center justify-between">
                            <dt class="text-xs text-base-content/50">Username</dt>
                            <dd class="ml-0 text-sm font-medium text-base-content">{{ $account?->username }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-xs text-base-content/50">Role</dt>
                            <dd class="ml-0 text-sm font-medium text-base-content">{{ $account?->role ?: '—' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="w-full lg:w-3/5">
                    <x-registry.card title="Change Your Password"
                                     description="You will stay signed in on this device">
                        <form method="POST" action="{{ route('change-password.store') }}">
                            @csrf

                            <div class="mb-3 w-full">
                                <x-registry.field name="current_password" label="Current Password"
                                                  type="password" required
                                                  placeholder="The password you signed in with" />
                            </div>

                            <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                                <x-registry.field name="password" label="New Password"
                                                  type="password" required
                                                  placeholder="At least 8 characters" />
                                <x-registry.field name="password_confirmation" label="Confirm New Password"
                                                  type="password" required
                                                  placeholder="The same again" />
                            </div>

                            <div class="py-3 flex flex-wrap items-center justify-end gap-2">
                                <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">Change Password</button>
                                <button type="reset" class="btn btn-sm btn-cta-outline px-4">Reset</button>
                            </div>
                        </form>
                    </x-registry.card>
                </div>
            </div>
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
