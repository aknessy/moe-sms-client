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
            <div class="gap-x-4 flex w-full py-4 flex-1">
                <div class="w-full flex flex-col lg:w-2/5 px-4 py-2 bg-brown-50 border-brown-100 rounded-md">

                </div>
                <div class="w-full lg:w-3/5 px-4 py-2 bg-brown-50 border-brown-100 rounded-md">
                    <div class="flex flex-col items-start">
                        <h2 class="text-md font-semibold mb-0">Profile Avatar</h2>
                        <p class="font-normal text-lg text-slate-500 m-0">Choose a prefered avatar</p>
                    </div>
                    <div class="py-4">
                        <form type="post" accept-charset="" action="">
                            @csrf
                            <div class="grid grid-cols-4 gap-2">
                                @foreach($resources as $avatar)
                                    <div class="bg-transparent rounded-lg hover:bg-brown-50 p-2 cursor-pointer">
                                        <img src="{{ Vite::asset($avatar->url) }}" class="h-20 w-20 rounded-lg" alt="{{ $avatar->name }}" />
                                        <input type="hidden" name="res-avatar" value="{{ $avatar->id }}" />
                                    </div>
                                @endforeach
                            </div>
                            <div class="py-3 px-4">
                                <button type="submit" class="rounded-md bg-gradient-to-tr from-slate-600 to-slate-700 py-2 px-4 border border-transparent text-center text-sm text-white transition-all shadow-md hover:shadow-lg focus:bg-slate-600 focus:shadow-none active:bg-slate-700 hover:bg-slate-700 hover:border-slate-800 active:border-slate-800 focus:border-slate-800 active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none">
                                    Save Preference
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>
</x-dashboard>
