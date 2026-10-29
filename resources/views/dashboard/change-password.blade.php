<x-dashboard :pageTitle="$page_title">
    <x-slot name="slot">
        <div class="w-full flex flex-col bg-transparent">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <nav aria-label="breadcrumb" class="w-full sm:w-1/3 space-y-2">
                    <ol class="flex w-full flex-wrap items-center gap-1.5 py-2 text-xs">
                        <li class="flex cursor-pointer items-center gap-1.5 text-base-content/60 transition-colors duration-300 hover:text-[#2E7D32]">
                            <a href="#">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                                </svg>
                            </a>
                            <span class="pointer-events-none text-base-content/30">/</span>
                        </li>
                        <li class="flex items-center text-base-content/60 transition-colors duration-300 cursor-not-allowed">
                            <a href="#">Profile Settings</a>
                        </li>
                    </ol>
                </nav>
                <div class="w-full sm:w-2/3">
                    <h1 class="font-alkalami text-sm font-semibold uppercase tracking-wide flex justify-end m-0">Staff Management - User Profile Settings</h1>
                </div>
            </div>
            <div class="flex w-full flex-1 flex-col gap-y-4 py-4 lg:flex-row lg:gap-x-8 lg:gap-y-0">
                <div class="w-full flex flex-col lg:w-2/5 px-4 py-4 bg-base-100 border border-base-300 rounded-md">
                    <div class="flex flex-col items-center gap-y-2">
                        <div class="bg-white px-1 py-1 rounded-full h-36 w-36">
                            <img src="{{ Vite::asset('resources/images/avatars/avatar-2.png') }}" class="h-full w-full rounded-full" alt="Placeholder Image" />
                        </div>
                        <h3 class="font-sans text-lg font-bold">Douglas Costa</h3>
                        <h4 class="text-sm font-normal text-base-content/70">Ministry of Establishment</h4>
                        <h5 class="text-base-content font-normal text-sm">douglas.acosta@domain.com</h5>
                    </div>
                    <div class="mt-5 flex flex-col gap-y-3">
                        <a href="{{ route('profile-settings') }}" class="flex items-center justify-start space-x-3 rounded-lg bg-transparent py-2 px-4 text-center text-sm hover:text-primary transition-al disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2">
                                <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"></path>
                                  <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"></path>
                            </svg>
                            <span class="">Personal Information</span>
                        </a>
                        <a href="{{ route('change-password') }}" class="flex items-center justify-start space-x-3 rounded-lg bg-primary text-primary-content py-2 px-4 text-center text-sm shadow-md transition-all hover:bg-primary/90">
                            <span class="rounded-full w-6 h-6 bg-primary-content/20 text-primary-content p-1 flex items-center justify-center">
                                <svg  xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2c5.523 0 10 4.477 10 10a10 10 0 0 1 -20 0c0 -5.523 4.477 -10 10 -10zm2 5a3 3 0 0 0 -2.98 2.65l-.015 .174l-.005 .176l.005 .176c.019 .319 .087 .624 .197 .908l.09 .209l-3.5 3.5l-.082 .094a1 1 0 0 0 0 1.226l.083 .094l1.5 1.5l.094 .083a1 1 0 0 0 1.226 0l.094 -.083l.083 -.094a1 1 0 0 0 0 -1.226l-.083 -.094l-.792 -.793l.585 -.585l.793 .792l.094 .083a1 1 0 0 0 1.403 -1.403l-.083 -.094l-.792 -.793l.792 -.792a3 3 0 1 0 1.293 -5.708zm0 2a1 1 0 1 1 0 2a1 1 0 0 1 0 -2z"></path>
                                </svg>
                            </span>
                            <span class="">Password</span>
                        </a>
                    </div>
                </div>
                <div class="w-full lg:w-3/5 px-4 py-2 bg-white border border-base-300 rounded-lg">
                    <div class="flex flex-col items-start">
                        <h2 class="font-alkalami text-base font-semibold mb-0 text-base-content/80">Credentials Information</h2>
                        <p class="font-sans text-sm font-normal text-base-content/60 m-0">System Login Credentials</p>
                    </div>
                    <div class="py-4 block bg-contain bg-right-top bg-no-repeat" style="background-image: url('{{Vite::asset('resources/images/user-png.png')}}');">
                        <form type="post" accept-charset="" action="">
                            @csrf

                            <div class="w-full mb-6">
                                <div class="w-full bg-neutral text-neutral-content px-3 py-3 rounded-lg">
                                    <p class="m-0 p-0 text-neutral-content text-sm">Your Login Credentials can only be altered or changed by an administrator</p>
                                </div>
                            </div>
                            <div class="form-input-area py-3 px-3 mt-3 border rounded-md border-base-300 gap-y-4">
                                <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                                    <div class="w-full max-w-sm min-w-[200px]">
                                        <label class="font-semibold font-sans text-sm text-base-content">Username</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/60 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 shadow-sm focus:shadow" name="userhandle" placeholder="Username" value="triggerHappy" readonly />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
                                                <path d="M8 9h8"></path>
                                                <path d="M8 13h6"></path>
                                                <path d="M13.5 19.5l-1.5 1.5l-3 -3h-3a3 3 0 0 1 -3 -3v-8a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v6"></path>
                                                <path d="M22 22l-5 -5"></path>
                                                <path d="M17 22l5 -5"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-sm min-w-[200px]">
                                        <label class="font-semibold font-sans text-sm text-base-content">Password</label>
                                        <div class="relative">
                                            <input type="password" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/60 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-base-300 hover:border-primary/50 shadow-sm focus:shadow" name="phone" placeholder="Phone number" value="secrethash" readonly />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
                                                <path d="M3 3m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z"></path>
                                                <path d="M8 4l2 0"></path>
                                                <path d="M9 17l0 .01"></path>
                                                <path d="M21 6l-2 3l2 3l-2 3l2 3"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="py-3 px-4 flex flex-wrap items-center justify-end gap-2">
                                <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md" disabled>
                                    Save Changes
                                </button>
                                <button type="reset" class="btn btn-sm btn-cta-outline px-4" disabled>
                                    Reset
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
