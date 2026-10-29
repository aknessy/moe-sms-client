<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Users, Roles & Permissions" crumb="Administration">
            <x-registry.card title="Open a Dashboard Account"
                             description="An account belongs to an officer already on the staff register. Their name and department are read from that record, so only the credentials are entered here.">
                @if (empty($staffOptions))
                    <p class="py-6 text-center text-sm text-base-content/60">
                        Every officer on the register already has an account.
                        Add the staff record first under Registry &rarr; Add Staff Record.
                    </p>
                @else
                    <form method="POST" action="{{ route('users-roles.store') }}">
                        @csrf

                        <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                            <x-registry.picker name="basic_information_ulid" label="Officer" :options="$staffOptions" required />
                            <x-registry.field name="username" label="Username" required placeholder="e.g. g.okon" />
                            <x-registry.field name="email" label="Email" type="email" required placeholder="name@inec.gov.ng" />
                        </div>

                        <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                            <x-registry.field name="password" label="Password" type="password" required placeholder="At least 8 characters" />
                            <x-registry.field name="password_confirmation" label="Confirm Password" type="password" required placeholder="Repeat the password" />
                            <x-registry.field name="role" label="Role" required :options="$roles" />
                        </div>

                        <div class="mb-3 w-full">
                            <span class="font-sans text-sm font-medium text-base-content">Permissions</span>
                            <p class="mt-0.5 mb-0 text-xs text-base-content/50">
                                Only what the chosen role allows. An account need not be given all of them.
                            </p>

                            {{-- Rebuilt from the chosen role; see the script below. --}}
                            <div class="mt-1 flex flex-wrap items-center gap-x-6 gap-y-2" data-account-permissions>
                                <span class="text-xs text-base-content/40" data-permissions-empty>Choose a role first.</span>

                                @foreach ($availablePermissions as $value => $label)
                                    <label class="hidden cursor-pointer items-center gap-2 text-xs text-base-content/80"
                                           data-permission="{{ $value }}">
                                        <input type="checkbox" name="permissions[]" value="{{ $value }}" disabled
                                               @checked(in_array($value, (array) old('permissions', []), true))
                                               class="h-4 w-4 rounded border-base-300 text-[#2E7D32] focus:ring-[#2E7D32]" />
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>

                            @error('permissions')
                                <p class="mt-1 text-xs text-error">{{ $message }}</p>
                            @enderror
                            @error('permissions.*')
                                <p class="mt-1 text-xs text-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-3 w-full">
                            <label class="flex cursor-pointer items-center gap-2 text-xs text-base-content/80">
                                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))
                                       class="h-4 w-4 rounded border-base-300 text-[#2E7D32] focus:ring-[#2E7D32]" />
                                Account is active and may sign in
                            </label>
                        </div>

                        <div class="py-3 flex flex-wrap items-center justify-end gap-2">
                            <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">Create Account</button>
                            <button type="reset" class="btn btn-sm btn-cta-outline px-4">Reset</button>
                        </div>
                    </form>
                @endif
            </x-registry.card>

            <x-registry.card title="Roles" :count="$roleRecords->count()"
                             description="What an account may be given. Permissions will be attached to these.">
                <x-registry.table :headings="['Role', 'Reference', 'Accounts', 'Permissions', 'Added by', 'Added']"
                                  empty="No roles defined.">
                    @foreach ($roleRecords as $role)
                        <tr class="hover:bg-base-200/60">
                            <td class="px-3 py-2 font-medium text-base-content">{{ $role->role }}</td>
                            <td class="px-3 py-2 font-mono text-[11px] text-base-content/50">{{ $role->role_ulid }}</td>
                            <td class="px-3 py-2">{{ $users->getCollection()->where('role_id', $role->id)->count() ?: '—' }}</td>
                            <td class="px-3 py-2">
                                {{-- The whole set is submitted, so clearing a
                                     box withdraws that permission. --}}
                                <form method="POST" action="{{ route('users-roles.permissions', $role) }}"
                                      class="flex flex-wrap items-center gap-x-4 gap-y-1">
                                    @csrf
                                    @foreach ($availablePermissions as $value => $label)
                                        <label class="flex cursor-pointer items-center gap-1 text-[11px] text-base-content/80">
                                            <input type="checkbox" name="permissions[]" value="{{ $value }}"
                                                   @checked($role->grants($value))
                                                   class="h-3.5 w-3.5 rounded border-base-300 text-[#2E7D32] focus:ring-[#2E7D32]" />
                                            {{ $label }}
                                        </label>
                                    @endforeach
                                    <button type="submit" class="text-[11px] text-[#2E7D32] hover:underline">Save</button>
                                </form>
                            </td>
                            <td class="px-3 py-2">
                                {{-- Null for the roles the system ships with. --}}
                                {{ $role->creator?->username ?: 'System' }}
                            </td>
                            <td class="px-3 py-2">{{ $role->created_at?->toDateString() ?: '—' }}</td>
                        </tr>
                    @endforeach
                </x-registry.table>
            </x-registry.card>

            <x-registry.card title="Dashboard Accounts" :count="$users->total()">
                <x-registry.table :headings="['Username', 'Officer', 'File / Computer No.', 'Department', 'Email', 'Role', 'Permissions', 'Status', 'Action']"
                                  empty="No dashboard accounts yet.">
                    @foreach ($users as $account)
                        <tr class="hover:bg-base-200/60">
                            <td class="px-3 py-2 font-medium text-base-content">{{ $account->username }}</td>
                            <td class="px-3 py-2">{{ $account->full_name ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $account->staff?->staff_number ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $account->department ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $account->email }}</td>
                            <td class="px-3 py-2">
                                {{ $account->role ?: '—' }}
                                @if ($account->is_admin)
                                    <span class="ml-1 rounded-full bg-[#2E7D32]/10 px-2 py-0.5 text-[11px] text-[#1B5E20]">admin</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                {{ collect(json_decode($account->permissions, true) ?: [])->map(fn ($p) => $availablePermissions[$p] ?? $p)->implode(', ') ?: '—' }}
                            </td>
                            <td class="px-3 py-2">
                                @if ($account->is_active)
                                    <span class="rounded-full bg-[#2E7D32]/10 px-2 py-1 text-xs text-[#1B5E20]">Active</span>
                                @else
                                    <span class="rounded-full bg-error/10 px-2 py-1 text-xs text-error">Disabled</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                <form method="POST" action="{{ route('users-roles.toggle', $account) }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-[#2E7D32] hover:underline">
                                        {{ $account->is_active ? 'Deactivate' : 'Reactivate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </x-registry.table>

                <x-registry.paginator :items="$users" />
            </x-registry.card>
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>

        <script>
            // The permissions offered against an account are the ones its role
            // allows, so choosing a role rebuilds the set.
            (function () {
                const role = document.getElementById('role');
                const area = document.querySelector('[data-account-permissions]');

                if (! role || ! area) {
                    return;
                }

                const allowedByRole = @json($permissionsByRole);
                const empty = area.querySelector('[data-permissions-empty]');
                const boxes = area.querySelectorAll('[data-permission]');

                function sync() {
                    const allowed = allowedByRole[role.value] || [];

                    boxes.forEach(function (label) {
                        const offered = allowed.includes(label.dataset.permission);
                        const input = label.querySelector('input');

                        label.classList.toggle('hidden', ! offered);
                        label.classList.toggle('flex', offered);

                        // A disabled box is not submitted, so a permission that
                        // is not offered cannot be sent.
                        input.disabled = ! offered;

                        if (! offered) {
                            input.checked = false;
                        }
                    });

                    empty.hidden = allowed.length > 0;
                    empty.textContent = role.value
                        ? 'This role allows nothing yet — grant it some below.'
                        : 'Choose a role first.';
                }

                role.addEventListener('change', sync);
                sync();
            })();
        </script>
    </x-slot>
</x-dashboard>
