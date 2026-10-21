<x-dashboard pageTitle="{{ $page_title }}" :segments="$uri_segments">
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
                            <div class="mt-1 flex flex-wrap items-center gap-x-6 gap-y-2">
                                @foreach ($availablePermissions as $value => $label)
                                    <label class="flex cursor-pointer items-center gap-2 text-xs text-base-content/80">
                                        <input type="checkbox" name="permissions[]" value="{{ $value }}"
                                               @checked(in_array($value, (array) old('permissions', []), true))
                                               class="h-4 w-4 rounded border-base-300 text-[#2E7D32] focus:ring-[#2E7D32]" />
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                            @error('permissions')
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
                            <td class="px-3 py-2">{{ $roles[$account->role] ?? $account->role }}</td>
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
    </x-slot>
</x-dashboard>
