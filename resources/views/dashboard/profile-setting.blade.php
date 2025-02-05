<x-dashboard>
    <x-slot name="slot">
        <h1 class="text-xl font-semibold">App Management - Profile Settings</h1>
        <div class="w-full flex flex-col bg-transparent py-4">
            <nav aria-label="breadcrumb" class="w-full">
                <ol class="flex w-full flex-wrap items-center rounded-md px-4 py-3">
                    <li class="flex cursor-pointer items-center text-sm text-brown-600 transition-colors duration-300 hover:text-brown-700">
                        <a href="#">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                            </svg>
                        </a>
                        <span class="pointer-events-none mx-2 text-brown-600">/</span>
                    </li>
                    <li class="flex items-center text-sm text-brown-500 transition-colors duration-300 cursor-not-allowed">
                        <a href="#">Profile Settings</a>
                    </li>
                </ol>
            </nav>
            <div class="gap-x-4 flex w-full py-4 flex-1">
                <div class="w-full flex flex-col lg:w-2/5 px-4 py-2 bg-brown-50 rounded-md">
                    <div class="flex flex-col items-start">
                        <h2 class="text-md font-semibold mb-0">Profile Avatar</h2>
                        <p class="font-normal text-lg text-slate-500 m-0">Choose a prefered avatar</p>
                    </div>
                    <div class="py-4">
                        <form type="post" accept-charset="" typeof="">
                            @csrf
                            <div class="grid grid-cols-4 gap-4">
                                @foreach($resources as $avatar)
                                    <div class="bg-transparent rounded-lg">
                                        <img src="{{ Vite::asset($avatar->url) }}" class="h-24 w-24 rounded-lg" alt="{{ $avata->name }}" />
                                    </div>
                                @endforeach
                            </div>
                        </form>
                    </div>
                </div>
                <div class="w-full lg:w-3/5 px-4 py-2 bg-brown-50 rounded-md"></div>
            </div>
        </div>
    </x-slot>
</x-dashboard>
