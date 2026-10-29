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
                        <li class="flex text-base-content/60 items-center transition-colors duration-300 cursor-not-allowed">
                            <a href="#">Profile Settings</a>
                        </li>
                    </ol>
                </nav>
                <div class="w-full sm:w-2/3">
                    <h5 class="font-alkalami text-sm font-semibold uppercase tracking-wide flex justify-end m-0">Staff Management - User Profile Settings</h5>
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
                        <h5 class="text-base-content/80 font-normal text-sm">douglas.acosta@domain.com</h5>
                    </div>
                    <div class="mt-5 flex flex-col gap-y-3">
                        <a href="{{ route('profile-settings') }}" class="flex items-center justify-start space-x-3 rounded-lg bg-primary text-primary-content py-2 px-4 text-center text-sm shadow-md transition-all hover:bg-primary/90">
                            <span class="rounded-full w-6 h-6 bg-primary-content/20 text-primary-content p-1 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2">
                                  <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"></path>
                                  <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"></path>
                                </svg>
                            </span>
                            <span class="">Personal Information</span>
                        </a>
                        <a href="{{ route('change-password') }}" class="flex items-center justify-start space-x-3 rounded-lg bg-transparent py-2 px-4 text-center text-sm hover:text-primary transition-al disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none">
                            <svg  xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2c5.523 0 10 4.477 10 10a10 10 0 0 1 -20 0c0 -5.523 4.477 -10 10 -10zm2 5a3 3 0 0 0 -2.98 2.65l-.015 .174l-.005 .176l.005 .176c.019 .319 .087 .624 .197 .908l.09 .209l-3.5 3.5l-.082 .094a1 1 0 0 0 0 1.226l.083 .094l1.5 1.5l.094 .083a1 1 0 0 0 1.226 0l.094 -.083l.083 -.094a1 1 0 0 0 0 -1.226l-.083 -.094l-.792 -.793l.585 -.585l.793 .792l.094 .083a1 1 0 0 0 1.403 -1.403l-.083 -.094l-.792 -.793l.792 -.792a3 3 0 1 0 1.293 -5.708zm0 2a1 1 0 1 1 0 2a1 1 0 0 1 0 -2z"></path>
                            </svg>
                            <span class="">Password</span>
                        </a>
                    </div>
                </div>
                <div class="w-full lg:w-3/5 px-4 py-2 bg-white border border-base-300 rounded-lg">
                    <div class="flex flex-col items-start">
                        <h2 class="font-alkalami text-base font-semibold mb-0 text-base-content/80">Personal Information</h2>
                        <p class="font-sans text-sm font-normal text-base-content/60 m-0">User's personal Information</p>
                    </div>
                    <div class="py-4 block bg-contain bg-right-top bg-no-repeat" style="background-image: url('{{Vite::asset('resources/images/user-png.png')}}');">
                        <form type="post" accept-charset="" action="">
                            @csrf

                            <div class="lg:w-[400px] w-1/2">
                                <label class="font-sans text-sm font-semibold text-base-content">Choose Avatar</label>
                                <div class="grid grid-cols-4 gap-2">
                                    @foreach($resources as $avatar)
                                        <div class="bg-transparent rounded-lg hover:bg-base-200 p-2 cursor-pointer">
                                            <img src="{{ Vite::asset($avatar->url) }}" class="h-20 w-20 rounded-lg" alt="{{ $avatar->name }}" />
                                            <input type="hidden" name="res-avatar" value="{{ $avatar->id }}" />
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="form-input-area py-3 px-3 mt-3 border rounded-md border-base-300 gap-y-4">
                                <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                                    <div class="w-full max-w-sm">
                                        <label class="font-semibold font-sans text-sm text-base-content">Surname</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 shadow-sm focus:shadow" name="last-name" placeholder="Surname" value="" />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
                                                <path d="M3 17c3.333 -3.333 5 -6 5 -8c0 -3 -1 -3 -2 -3s-2.032 1.085 -2 3c.034 2.048 1.658 4.877 2.5 6c1.5 2 2.5 2.5 3.5 1l2 -3c.333 2.667 1.333 4 3 4c.53 0 2.639 -2 3 -2c.517 0 1.517 .667 3 2"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-sm">
                                        <label class="font-semibold font-sans text-sm text-base-content">First name</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 shadow-sm focus:shadow" name="first-name" placeholder="First Name" value="" />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
                                                <path d="M3 17c3.333 -3.333 5 -6 5 -8c0 -3 -1 -3 -2 -3s-2.032 1.085 -2 3c.034 2.048 1.658 4.877 2.5 6c1.5 2 2.5 2.5 3.5 1l2 -3c.333 2.667 1.333 4 3 4c.53 0 2.639 -2 3 -2c.517 0 1.517 .667 3 2"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-sm">
                                        <label class="font-semibold font-sans text-sm text-base-content">Other names</label>
                                        <div class="relative">
                                            <input type="password" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 shadow-sm focus:shadow" name="other-names" placeholder="Other Names" value />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
                                                <path d="M3 17c3.333 -3.333 5 -6 5 -8c0 -3 -1 -3 -2 -3s-2.032 1.085 -2 3c.034 2.048 1.658 4.877 2.5 6c1.5 2 2.5 2.5 3.5 1l2 -3c.333 2.667 1.333 4 3 4c.53 0 2.639 -2 3 -2c.517 0 1.517 .667 3 2"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                                    <div class="w-full max-w-sm min-w-[200px]">
                                        <label class="font-semibold font-sans text-sm text-base-content">Email</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 shadow-sm focus:shadow" name="email" placeholder="Email" value="" />
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
                                        <label class="font-semibold font-sans text-sm text-base-content">Phone</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 shadow-sm focus:shadow" name="phone" placeholder="Phone number" value="" />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
                                                <path d="M3 3m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z"></path>
                                                <path d="M8 4l2 0"></path>
                                                <path d="M9 17l0 .01"></path>
                                                <path d="M21 6l-2 3l2 3l-2 3l2 3"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                                    <div class="w-full max-w-sm min-w-[200px]">
                                        <label class="font-semibold font-sans text-sm text-base-content">Department</label>
                                        <div class="relative">
                                            <select class="w-full bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded pl-3 pr-8 py-2 transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 shadow-sm focus:shadow-md appearance-none cursor-pointer" name="department">
                                                <option value="brazil">Brazil</option>
                                                <option value="bucharest">Bucharest</option>
                                                <option value="london">London</option>
                                                <option value="washington">Washington</option>
                                            </select>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
                                                <path d="M9 18l3 3l3 -3"></path>
                                                <path d="M12 15v6"></path>
                                                <path d="M15 6l-3 -3l-3 3"></path>
                                                <path d="M12 3v6"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-sm min-w-[200px]">
                                        <label class="font-semibold font-sans text-sm text-base-content">Unit</label>
                                        <div class="relative">
                                            <select class="w-full bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded pl-3 pr-8 py-2 transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 shadow-sm focus:shadow-md appearance-none cursor-pointer" name="unit">
                                                <option value="brazil">Brazil</option>
                                                <option value="bucharest">Bucharest</option>
                                                <option value="london">London</option>
                                                <option value="washington">Washington</option>
                                            </select>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
                                                <path d="M9 18l3 3l3 -3"></path>
                                                <path d="M12 15v6"></path>
                                                <path d="M15 6l-3 -3l-3 3"></path>
                                                <path d="M12 3v6"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div class="w-full">
                                    <p class="font-semibold font-sans text-sm text-base-content">Gender</p>
                                    <div class="flex gap-10">
                                        <div class="inline-flex items-center">
                                            <label
                                                class="relative flex cursor-pointer items-center rounded-full p-3"
                                                for="on"
                                                data-ripple-dark="true"
                                            >
                                                <input name="gender" value="male"
                                                type="radio"
                                                class="before:content-[''] peer h-5 w-5 cursor-pointer appearance-none rounded-full border border-base-300 checked:border-primary transition-all before:absolute before:top-2/4 before:left-2/4 before:block before:h-12 before:w-12 before:-translate-y-2/4 before:-translate-x-2/4 before:rounded-full before:bg-primary before:opacity-0 before:transition-opacity hover:before:opacity-10"
                                                id="on"/>
                                                <span class="absolute bg-primary w-3 h-3 rounded-full opacity-0 peer-checked:opacity-100 transition-opacity duration-200 top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2"></span>
                                            </label>
                                            <label
                                                class="text-base-content/80 cursor-pointer text-sm"
                                                for="on"
                                            >
                                            Female
                                        </label>
                                        </div>
                                        <div class="inline-flex items-center">
                                            <label
                                                class="relative flex cursor-pointer items-center rounded-full p-3"
                                                for="on"
                                                data-ripple-dark="true"
                                                >
                                                    <input name="gender" value="male"
                                                    type="radio"
                                                    class="before:content-[''] peer h-5 w-5 cursor-pointer appearance-none rounded-full border border-base-300 checked:border-primary transition-all before:absolute before:top-2/4 before:left-2/4 before:block before:h-12 before:w-12 before:-translate-y-2/4 before:-translate-x-2/4 before:rounded-full before:bg-primary before:opacity-0 before:transition-opacity hover:before:opacity-10"
                                                    id="on"
                                                    />
                                                    <span class="absolute bg-primary w-3 h-3 rounded-full opacity-0 peer-checked:opacity-100 transition-opacity duration-200 top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2"></span>
                                                </label>
                                                <label
                                                    class="text-base-content/80 cursor-pointer text-sm"
                                                    for="on"
                                                >
                                                Male
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="py-3 px-4 flex flex-wrap items-center justify-end gap-2">
                                <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">
                                    Save Changes
                                </button>
                                <button type="reset" class="btn btn-sm btn-cta-outline px-4">
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
