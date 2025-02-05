<x-dashboard pageTitle="{{ $page_title }}">
    <x-slot name="slot">
        <div class="w-full flex flex-col bg-transparent">
            <div class="flex items-center justify-between">
                <nav aria-label="breadcrumb" class="w-1/3 space-y-2">
                    <ol class="flex w-full flex-wrap items-center rounded-md px-4 py-3">
                        <li class="flex cursor-pointer items-center text-lg/3 text-brown-600 transition-colors duration-300 hover:text-brown-700">
                            <a href="#">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                                </svg>
                            </a>
                            <span class="pointer-events-none mx-2 text-brown-600">/</span>
                        </li>
                        <li class="flex items-center text-lg/3 text-brown-500 transition-colors duration-300 cursor-not-allowed">
                            <a href="#">Profile Settings</a>
                        </li>
                    </ol>
                </nav>
                <div class="w-2/3 sm:w-full">
                    <h1 class="text-lg/3 font-semibold flex space-y-2 justify-end m-0">App Management - Profile Settings</h1>
                </div>
            </div>
            <div class="lg:gap-x-8 gap-x-2 flex w-full py-4 flex-1">
                <div class="w-full flex flex-col lg:w-2/5 px-4 py-2 bg-brown-50 border border-brown-100 rounded-md"></div>
                <div class="w-full lg:w-3/5 px-4 py-2 bg-brown-50 border border-brown-100 rounded-lg">
                    <div class="flex flex-col items-start">
                        <h2 class="text-lg font-semibold mb-0">Personal Information</h2>
                        <p class="font-normal text-md text-slate-500 m-0">User's personal Information</p>
                    </div>
                    <div class="py-4 block">
                        <form type="post" accept-charset="" action="">
                            @csrf

                            <div class="lg:w-[400px] w-1/2">
                                <label class="font-semibold text-lg">Choose Avatar</label>
                                <div class="grid grid-cols-4 gap-2">
                                    @foreach($resources as $avatar)
                                        <div class="bg-transparent rounded-lg hover:bg-brown-50 p-2 cursor-pointer">
                                            <img src="{{ Vite::asset($avatar->url) }}" class="h-20 w-20 rounded-lg" alt="{{ $avatar->name }}" />
                                            <input type="hidden" name="res-avatar" value="{{ $avatar->id }}" />
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="form-input-area py-3 px-3 mt-3 border rounded-md border-brown-100 gap-y-4">
                                <div class="mb-2 w-full lg:flex lg:space-x-4 space-x-2">
                                    <div class="w-full max-w-sm min-w-[200px]">
                                        <label class="font-semibold font-sans text-md text-brown-900">Surname</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-brown-50 placeholder:text-brown-400 text-brown-600 text-sm border border-brown-200 rounded-md transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 shadow-sm focus:shadow" name="last-name" placeholder="Surname" />
                                            <svg  xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" class="absolute w-5 h-5 top-2.5 right-2.5 text-brown-700">
                                                <path d="M12 2c5.523 0 10 4.477 10 10a10 10 0 0 1 -20 0c0 -5.523 4.477 -10 10 -10zm2 5a3 3 0 0 0 -2.98 2.65l-.015 .174l-.005 .176l.005 .176c.019 .319 .087 .624 .197 .908l.09 .209l-3.5 3.5l-.082 .094a1 1 0 0 0 0 1.226l.083 .094l1.5 1.5l.094 .083a1 1 0 0 0 1.226 0l.094 -.083l.083 -.094a1 1 0 0 0 0 -1.226l-.083 -.094l-.792 -.793l.585 -.585l.793 .792l.094 .083a1 1 0 0 0 1.403 -1.403l-.083 -.094l-.792 -.793l.792 -.792a3 3 0 1 0 1.293 -5.708zm0 2a1 1 0 1 1 0 2a1 1 0 0 1 0 -2z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-sm min-w-[200px]">
                                        <label class="font-semibold font-sans text-md text-brown-900">First name</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-brown-50 placeholder:text-brown-400 text-brown-600 text-sm border border-brown-200 rounded-md transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 shadow-sm focus:shadow" name="first-name" placeholder="First Name" />
                                            <svg  xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" class="absolute w-5 h-5 top-2.5 right-2.5 text-brown-700">
                                                <path d="M12 2c5.523 0 10 4.477 10 10a10 10 0 0 1 -20 0c0 -5.523 4.477 -10 10 -10zm2 5a3 3 0 0 0 -2.98 2.65l-.015 .174l-.005 .176l.005 .176c.019 .319 .087 .624 .197 .908l.09 .209l-3.5 3.5l-.082 .094a1 1 0 0 0 0 1.226l.083 .094l1.5 1.5l.094 .083a1 1 0 0 0 1.226 0l.094 -.083l.083 -.094a1 1 0 0 0 0 -1.226l-.083 -.094l-.792 -.793l.585 -.585l.793 .792l.094 .083a1 1 0 0 0 1.403 -1.403l-.083 -.094l-.792 -.793l.792 -.792a3 3 0 1 0 1.293 -5.708zm0 2a1 1 0 1 1 0 2a1 1 0 0 1 0 -2z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-sm min-w-[200px]">
                                        <label class="font-semibold font-sans text-md text-brown-900">Other names</label>
                                        <div class="relative">
                                            <input type="password" class="w-full pl-3 pr-10 py-2 bg-brown-50 placeholder:text-brown-400 text-brown-600 text-sm border border-brown-200 rounded-md transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 shadow-sm focus:shadow" name="other-names" placeholder="Other Names" />
                                            <svg  xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" class="absolute w-5 h-5 top-2.5 right-2.5 text-brown-700">
                                                <path d="M12 2c5.523 0 10 4.477 10 10a10 10 0 0 1 -20 0c0 -5.523 4.477 -10 10 -10zm2 5a3 3 0 0 0 -2.98 2.65l-.015 .174l-.005 .176l.005 .176c.019 .319 .087 .624 .197 .908l.09 .209l-3.5 3.5l-.082 .094a1 1 0 0 0 0 1.226l.083 .094l1.5 1.5l.094 .083a1 1 0 0 0 1.226 0l.094 -.083l.083 -.094a1 1 0 0 0 0 -1.226l-.083 -.094l-.792 -.793l.585 -.585l.793 .792l.094 .083a1 1 0 0 0 1.403 -1.403l-.083 -.094l-.792 -.793l.792 -.792a3 3 0 1 0 1.293 -5.708zm0 2a1 1 0 1 1 0 2a1 1 0 0 1 0 -2z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-2 w-full lg:flex lg:space-x-4 space-x-2">
                                    <div class="w-full max-w-sm min-w-[200px]">
                                        <label class="font-semibold font-sans text-md text-brown-900">Email</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-brown-50 placeholder:text-brown-400 text-brown-600 text-sm border border-brown-200 rounded-md transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 shadow-sm focus:shadow" name="email" placeholder="Email" />
                                            <svg  xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" class="absolute w-5 h-5 top-2.5 right-2.5 text-brown-700">
                                                <path d="M12 2c5.523 0 10 4.477 10 10a10 10 0 0 1 -20 0c0 -5.523 4.477 -10 10 -10zm2 5a3 3 0 0 0 -2.98 2.65l-.015 .174l-.005 .176l.005 .176c.019 .319 .087 .624 .197 .908l.09 .209l-3.5 3.5l-.082 .094a1 1 0 0 0 0 1.226l.083 .094l1.5 1.5l.094 .083a1 1 0 0 0 1.226 0l.094 -.083l.083 -.094a1 1 0 0 0 0 -1.226l-.083 -.094l-.792 -.793l.585 -.585l.793 .792l.094 .083a1 1 0 0 0 1.403 -1.403l-.083 -.094l-.792 -.793l.792 -.792a3 3 0 1 0 1.293 -5.708zm0 2a1 1 0 1 1 0 2a1 1 0 0 1 0 -2z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="py-3 px-4 flex items-center justify-end gap-2">
                                <button type="submit" class="rounded-md bg-gradient-to-tr from-slate-600 to-slate-700 py-2 px-4 border border-transparent text-center text-sm text-white transition-all shadow-md hover:shadow-lg focus:bg-slate-600 focus:shadow-none active:bg-slate-700 hover:bg-slate-700 hover:border-slate-800 active:border-slate-800 focus:border-slate-800 active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none">
                                    Save Preference
                                </button>
                                <button type="reset" class="rounded-md border border-brown-200 py-2 px-4 text-center text-sm transition-all shadow-sm hover:shadow-lg text-brown-600 hover:text-white hover:bg-brown-300 hover:border-gray-50 focus:text-white focus:bg-brown-300 focus:border-gray-50 active:border-gray-50 active:text-white active:bg-brown-300">
                                    Reset
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>
</x-dashboard>
